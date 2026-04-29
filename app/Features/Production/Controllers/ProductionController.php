<?php

namespace App\Features\Production\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Production\Models\Production;
use App\Features\Lines\Models\Line;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $data = Production::with(['line', 'items.lot.glGroup.customer', 'items.details'])
            ->whereDate('production_date', $date)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function dashboard(Request $request)
    {
        $range = $request->get('range', 7);
        $days = (int) $range;

        // Anchor range from the LAST recorded input date (not today)
        $lastInputDate = Production::max('production_date');

        if (!$lastInputDate) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'recent_entries'  => [],
                    'output_chart'    => [],
                    'active_lines'    => [],
                    'last_input_date' => null,
                    'today_date'      => now()->toDateString(),
                ]
            ]);
        }

        $endDate   = $lastInputDate;
        $startDate = \Carbon\Carbon::parse($lastInputDate)->subDays($days - 1)->toDateString();

        // 1. Last 10 GL/Lot entries
        $lastEntries = \App\Features\Production\Models\ProductionItem::with(['lot.glGroup.customer', 'production.line'])
            ->latest()
            ->limit(10)
            ->get();

        // 2. Output chart based on range
        $outputChartQuery = \App\Features\Production\Models\ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->whereDate('productions.production_date', '>=', $startDate)
            ->whereDate('productions.production_date', '<=', $endDate)
            ->select(
                DB::raw('DATE(productions.production_date) as date_val'),
                DB::raw('SUM(qty_input) as total_input'),
                DB::raw('SUM(qty_output) as total_output')
            )
            ->groupBy('date_val')
            ->orderBy('date_val', 'ASC')
            ->get();

        // Transform chart data to ensure keys match frontend expectations
        $outputChart = $outputChartQuery->map(fn($item) => [
            'date' => $item->date_val,
            'total_input' => $item->total_input,
            'total_output' => $item->total_output
        ]);

        // 3. Active lines in the range
        $activeLines = Production::whereDate('production_date', '>=', $startDate)
            ->whereDate('production_date', '<=', $endDate)
            ->with(['line', 'items.details'])
            ->get()
            ->pluck('line')
            ->filter()
            ->unique('id')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'recent_entries'  => $lastEntries,
                'output_chart'    => $outputChart,
                'active_lines'    => $activeLines,
                'last_input_date' => $lastInputDate,
                'today_date'      => now()->toDateString(),
            ]
        ]);
    }

    public function lines()
    {
        return response()->json([
            'status' => 'success',
            'data' => Line::all()
        ]);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'lot_id' => 'required|exists:lots,id',
            'color' => 'required|string',
            'exclude_production_id' => 'nullable|integer',
        ]);

        $query = \App\Features\Production\Models\ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->where('production_items.lot_id', $validated['lot_id'])
            ->where('production_items.color', $validated['color']);

        // Exclude the current production record when editing
        // to avoid double-counting the record's own output in the balance
        if (!empty($validated['exclude_production_id'])) {
            $query->where('production_items.production_id', '!=', $validated['exclude_production_id']);
        }

        $summary = $query
            ->select('size_name', DB::raw('SUM(qty_input) as total_input'), DB::raw('SUM(qty_output) as total_output'))
            ->groupBy('size_name')
            ->get()
            ->keyBy('size_name');

        return response()->json([
            'status' => 'success',
            'data' => $summary
        ]);
    }

    public function bulkSummary(Request $request)
    {
        $lotIds = $request->get('lot_ids', []);
        
        if (empty($lotIds)) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $summaries = \App\Features\Production\Models\ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->whereIn('production_items.lot_id', $lotIds)
            ->select('production_items.lot_id', DB::raw('SUM(qty_output) as total_output'))
            ->groupBy('production_items.lot_id')
            ->get()
            ->keyBy('lot_id');

        return response()->json([
            'status' => 'success',
            'data' => $summaries
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'line_id' => 'required|exists:lines,id',
            'production_date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array',
            'items.*.lot_id' => 'required|exists:lots,id',
            'items.*.color' => 'required|string',
            'items.*.section' => 'nullable|string',
            'items.*.remarks' => 'nullable|string',
            'items.*.sizes' => 'required|array',
            'items.*.sizes.*.size_name' => 'required|string',
            'items.*.sizes.*.qty_input' => 'required|integer',
            'items.*.sizes.*.qty_output' => 'required|integer',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $production = Production::create([
                'line_id' => $validated['line_id'],
                'production_date' => $validated['production_date'],
                'man_power_sewer' => 0,
                'man_power_matching' => 0,
                'man_power_qc' => 0,
                'man_power_others' => 0,
                'remarks' => $request->get('remarks'),
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = $production->items()->create([
                    'lot_id' => $itemData['lot_id'],
                    'color' => $itemData['color'],
                    'section' => $itemData['section'] ?? 'all',
                    'remarks' => $itemData['remarks'] ?? null
                ]);

                foreach ($itemData['sizes'] as $sizeData) {
                    $item->details()->create($sizeData);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $production->load(['line', 'items.lot', 'items.details'])
            ], 201);
        });
    }

    public function show($id)
    {
        $production = Production::with(['line', 'items.lot.glGroup', 'items.details'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $production
        ]);
    }

    public function update(Request $request, $id)
    {
        $production = Production::findOrFail($id);
        
        $validated = $request->validate([
            'line_id' => 'required|exists:lines,id',
            'production_date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array',
            'items.*.lot_id' => 'required|exists:lots,id',
            'items.*.color' => 'required|string',
            'items.*.section' => 'nullable|string',
            'items.*.remarks' => 'nullable|string',
            'items.*.sizes' => 'required|array',
            'items.*.sizes.*.size_name' => 'required|string',
            'items.*.sizes.*.qty_input' => 'required|integer',
            'items.*.sizes.*.qty_output' => 'required|integer',
        ]);

        return DB::transaction(function () use ($validated, $production, $request) {
            $production->update([
                'line_id' => $validated['line_id'],
                'production_date' => $validated['production_date'],
                'remarks' => $request->get('remarks'),
            ]);

            // Sync items: Delete existing and recreate (simplest for these nested structures)
            foreach ($production->items as $item) {
                $item->details()->delete();
                $item->delete();
            }

            foreach ($validated['items'] as $itemData) {
                $item = $production->items()->create([
                    'lot_id' => $itemData['lot_id'],
                    'color' => $itemData['color'],
                    'section' => $itemData['section'] ?? 'all',
                    'remarks' => $itemData['remarks'] ?? null
                ]);

                foreach ($itemData['sizes'] as $sizeData) {
                    $item->details()->create($sizeData);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $production->load(['line', 'items.lot', 'items.details'])
            ]);
        });
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'lot_id' => 'required|exists:lots,id',
            'color' => 'required|string',
            'size_name' => 'required|string',
        ]);

        $history = \App\Features\Production\Models\ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->leftJoin('lines', 'productions.line_id', '=', 'lines.id')
            ->where('production_items.lot_id', $validated['lot_id'])
            ->where('production_items.color', $validated['color'])
            ->where('production_item_details.size_name', $validated['size_name'])
            ->select(
                'productions.production_date',
                'lines.name as line_name',
                'production_item_details.qty_input',
                'production_item_details.qty_output'
            )
            ->orderBy('productions.production_date', 'DESC')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    public function destroy($id)
    {
        Production::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Production log deleted']);
    }
}

