<?php

namespace App\Features\Wip\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\Lot;
use App\Features\Production\Models\ProductionItemDetail;
use App\Features\Packing\Models\PackingItemDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WipReportController extends Controller
{
    public function summary()
    {
        // 1. Fetch all lots with their relationships
        $lots = Lot::with(['glGroup.customer'])
            ->where('is_cancelled', false)
            ->get();

        // 2. Fetch Aggregated Sewing Output per Lot
        $sewingSummaries = ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->join('lines', 'productions.line_id', '=', 'lines.id')
            ->select(
                'production_items.lot_id',
                DB::raw('SUM(qty_output) as total_output'),
                DB::raw('GROUP_CONCAT(DISTINCT lines.location SEPARATOR ", ") as locations')
            )
            ->groupBy('production_items.lot_id')
            ->get()
            ->keyBy('lot_id');

        // 3. Fetch Aggregated Packing Output per Lot
        $packingSummaries = PackingItemDetail::join('packing_items', 'packing_item_details.packing_item_id', '=', 'packing_items.id')
            ->select(
                'packing_items.lot_id',
                DB::raw('SUM(qty_output) as total_output')
            )
            ->groupBy('packing_items.lot_id')
            ->get()
            ->keyBy('lot_id');

        // We might need Cutting Output too. For now, since it's external, I'll return it as 0
        // until we decide how to sync it.
        
        $reportData = $lots->map(function ($lot) use ($sewingSummaries, $packingSummaries) {
            $sewing = $sewingSummaries->get($lot->id);
            $packing = $packingSummaries->get($lot->id);

            return [
                'lot_id' => $lot->id,
                'gl_lot' => $lot->lot_code,
                'customer' => $lot->glGroup->customer->name ?? '-',
                'brand' => $lot->brand ?? '-',
                'style_no' => $lot->style_no ?? '-',
                'product_type' => '', // placeholder
                'ex_fty_date' => '', // placeholder
                'fty' => $sewing->locations ?? '-',
                'gmt_delivery_date' => $lot->delivery_date ?? '-',
                'order_qty_dz' => 0, // placeholder
                'order_qty_pcs' => (int) $lot->gmt_qty,
                
                // CUTTING (Data not yet available locally, fetch from external if needed)
                'cutting_acc_output' => 0, 
                
                // SEWING
                'sewing_acc_output' => (int) ($sewing->total_output ?? 0),
                
                // PACKING
                'packing_acc_output' => (int) ($packing->total_output ?? 0),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $reportData
        ]);
    }
}
