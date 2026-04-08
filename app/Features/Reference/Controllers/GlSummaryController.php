<?php

namespace App\Features\Reference\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\GlGroup;
use App\Features\Reference\Models\Lot;
use App\Features\Production\Models\ProductionItem;
use App\Features\Production\Models\ProductionItemDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GlSummaryController extends Controller
{
    public function show($id)
    {
        $glGroup = GlGroup::with('customer', 'lots')->findOrFail($id);
        $lotIds = $glGroup->lots->pluck('id');

        // Total Order Qty
        $totalOrder = $glGroup->lots->sum('gmt_qty');

        // Total Production Output
        $productionData = ProductionItem::whereIn('lot_id', $lotIds)
            ->with(['details'])
            ->get();

        $totalOutput = 0;
        $colorBreakdown = [];
        $sizeBreakdown = [];

        foreach ($productionData as $item) {
            $color = $item->color;
            if (!isset($colorBreakdown[$color])) {
                $colorBreakdown[$color] = 0;
            }

            foreach ($item->details as $detail) {
                $totalOutput += $detail->qty_output;
                $colorBreakdown[$color] += $detail->qty_output;

                $size = $detail->size_name;
                if (!isset($sizeBreakdown[$size])) {
                    $sizeBreakdown[$size] = 0;
                }
                $sizeBreakdown[$size] += $detail->qty_output;
            }
        }

        // Format for response
        $colorResults = [];
        foreach ($colorBreakdown as $name => $qty) {
            $colorResults[] = ['name' => $name, 'qty' => $qty];
        }

        $sizeResults = [];
        foreach ($sizeBreakdown as $name => $qty) {
            $sizeResults[] = ['name' => $name, 'qty' => $qty];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'gl_info' => [
                    'id' => $glGroup->id,
                    'gl_number' => $glGroup->gl_number,
                    'customer' => $glGroup->customer->name,
                ],
                'summary' => [
                    'total_order' => $totalOrder,
                    'total_output' => $totalOutput,
                    'lots_count' => $glGroup->lots->count(),
                ],
                'breakdown' => [
                    'by_color' => $colorResults,
                    'by_size' => $sizeResults,
                ],
                'lots_details' => $glGroup->lots->map(function($lot) {
                    return [
                        'lot_number' => $lot->lot_number,
                        'order_qty' => $lot->gmt_qty,
                        'style_no' => $lot->style_no,
                    ];
                })
            ]
        ]);
    }
}
