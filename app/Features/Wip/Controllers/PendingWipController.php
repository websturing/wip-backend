<?php

namespace App\Features\Wip\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\Lot;
use App\Features\Production\Models\ProductionItemDetail;
use App\Features\Packing\Models\PackingItemDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PendingWipController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Query lots with output qty in sewing and packing
        $sewingQuery = ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->select('production_items.lot_id', DB::raw('SUM(qty_output) as output_qty'));

        $packingQuery = PackingItemDetail::join('packing_items', 'packing_item_details.packing_item_id', '=', 'packing_items.id')
            ->join('packings', 'packing_items.packing_id', '=', 'packings.id')
            ->select('packing_items.lot_id', DB::raw('SUM(qty_output) as output_qty'));

        if ($startDate && $endDate) {
            $sewingQuery->whereBetween('productions.production_date', [$startDate, $endDate]);
            $packingQuery->whereBetween('packings.packing_date', [$startDate, $endDate]);
        }

        $sewingOutput = $sewingQuery->groupBy('production_items.lot_id')->get()->keyBy('lot_id');
        $packingOutput = $packingQuery->groupBy('packing_items.lot_id')->get()->keyBy('lot_id');

        // Note: For cutting, we would ideally pull from cutting API, 
        // but for simplicity we will report Sewing and Packing pending.
        $lots = Lot::where('is_cancelled', false)->with('glGroup.customer')->get();

        $pendingSewing = [];
        $pendingPacking = [];

        foreach ($lots as $lot) {
            $orderQty = $lot->gmt_qty;
            
            $sewingQty = $sewingOutput->has($lot->id) ? $sewingOutput->get($lot->id)->output_qty : 0;
            $packingQty = $packingOutput->has($lot->id) ? $packingOutput->get($lot->id)->output_qty : 0;

            if ($orderQty - $sewingQty > 0) {
                $pendingSewing[] = [
                    'gl_number' => $lot->lot_code,
                    'customer' => $lot->glGroup->customer->name ?? '-',
                    'order_qty' => $orderQty,
                    'output_qty' => (int) $sewingQty,
                    'balance' => $orderQty - $sewingQty,
                    'service' => 'Sewing'
                ];
            }

            if ($orderQty - $packingQty > 0) {
                $pendingPacking[] = [
                    'gl_number' => $lot->lot_code,
                    'customer' => $lot->glGroup->customer->name ?? '-',
                    'order_qty' => $orderQty,
                    'output_qty' => (int) $packingQty,
                    'balance' => $orderQty - $packingQty,
                    'service' => 'Packing'
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'sewing' => $pendingSewing,
                'packing' => $pendingPacking
            ]
        ]);
    }
}
