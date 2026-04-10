<?php

namespace App\Features\Packing\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Packing\Models\Packing;
use App\Features\Packing\Models\PackingItem;
use App\Features\Packing\Models\PackingItemDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackingController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $data = Packing::with(['items.lot.glGroup', 'items.details'])
            ->whereDate('packing_date', $date)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'lot_id' => 'required|exists:lots,id',
            'color' => 'required|string',
        ]);

        $summary = PackingItemDetail::join('packing_items', 'packing_item_details.packing_item_id', '=', 'packing_items.id')
            ->where('packing_items.lot_id', $validated['lot_id'])
            ->where('packing_items.color', $validated['color'])
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

        $summaries = PackingItemDetail::join('packing_items', 'packing_item_details.packing_item_id', '=', 'packing_items.id')
            ->whereIn('packing_items.lot_id', $lotIds)
            ->select('packing_items.lot_id', DB::raw('SUM(qty_output) as total_output'))
            ->groupBy('packing_items.lot_id')
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
            'packing_date' => 'required|date',
            'man_power' => 'nullable|numeric',
            'remarks' => 'nullable|string',
            'items' => 'required|array',
            'items.*.lot_id' => 'required|exists:lots,id',
            'items.*.color' => 'required|string',
            'items.*.sizes' => 'required|array',
            'items.*.sizes.*.size_name' => 'required|string',
            'items.*.sizes.*.qty_input' => 'required|integer',
            'items.*.sizes.*.qty_output' => 'required|integer',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $packing = Packing::create([
                'packing_date' => $validated['packing_date'],
                'man_power' => $validated['man_power'] ?? 0,
                'remarks' => $request->get('remarks'),
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = $packing->items()->create([
                    'lot_id' => $itemData['lot_id'],
                    'color' => $itemData['color']
                ]);

                foreach ($itemData['sizes'] as $sizeData) {
                    $item->details()->create($sizeData);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $packing->load(['items.lot', 'items.details'])
            ], 201);
        });
    }

    public function destroy($id)
    {
        Packing::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Packing log deleted']);
    }
}
