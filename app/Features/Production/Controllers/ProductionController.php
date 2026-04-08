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

        $data = Production::with(['line', 'items.lot.glGroup', 'items.details'])
            ->whereDate('production_date', $date)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
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
        ]);

        $summary = \App\Features\Production\Models\ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->where('production_items.lot_id', $validated['lot_id'])
            ->where('production_items.color', $validated['color'])
            ->select('size_name', DB::raw('SUM(qty_input) as total_input'), DB::raw('SUM(qty_output) as total_output'))
            ->groupBy('size_name')
            ->get()
            ->keyBy('size_name');

        return response()->json([
            'status' => 'success',
            'data' => $summary
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'line_id' => 'required|exists:lines,id',
            'production_date' => 'required|date',
            'items' => 'required|array',
            'items.*.lot_id' => 'required|exists:lots,id',
            'items.*.color' => 'required|string',
            'items.*.sizes' => 'required|array',
            'items.*.sizes.*.size_name' => 'required|string',
            'items.*.sizes.*.qty_input' => 'required|integer',
            'items.*.sizes.*.qty_output' => 'required|integer',
        ]);

        return DB::transaction(function () use ($validated) {
            $production = Production::create([
                'line_id' => $validated['line_id'],
                'production_date' => $validated['production_date']
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = $production->items()->create([
                    'lot_id' => $itemData['lot_id'],
                    'color' => $itemData['color']
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

    public function destroy($id)
    {
        Production::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Production log deleted']);
    }
}
