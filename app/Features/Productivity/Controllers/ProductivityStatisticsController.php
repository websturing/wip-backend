<?php

namespace App\Features\Productivity\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\Lot;
use App\Features\Production\Models\ProductionItemDetail;
use App\Features\Production\Models\ProductionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductivityStatisticsController extends Controller
{
    public function detailedStatistics(Request $request)
    {
        // Get all active lots
        $lots = Lot::where('is_cancelled', false)->with('glGroup.customer')->get();

        // Get output grouped by lot_id and color
        $outputs = ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->select(
                'production_items.lot_id',
                'production_items.color',
                DB::raw('SUM(qty_output) as output_qty'),
                DB::raw('MIN(productions.production_date) as start_date'),
                DB::raw('MAX(productions.production_date) as last_update')
            )
            ->groupBy('production_items.lot_id', 'production_items.color')
            ->get();

        $data = [];

        foreach ($outputs as $out) {
            $lot = $lots->firstWhere('id', $out->lot_id);
            if (!$lot) continue;

            // Approximate order qty if color-specific order qty is not available in DB
            $orderQty = $lot->gmt_qty; // Default to whole lot qty, could be improved by connecting to Cutting API
            $balance = $out->output_qty - $orderQty;
            
            $startDate = Carbon::parse($out->start_date);
            $lastUpdate = Carbon::parse($out->last_update);
            $daysRunning = $startDate->diffInDays($lastUpdate) + 1;

            $achievement = $orderQty > 0 ? ($out->output_qty / $orderQty) * 100 : 0;

            $data[] = [
                'gl_number' => $lot->lot_code,
                'color' => $out->color,
                'order_qty' => $orderQty,
                'output_qty' => (int) $out->output_qty,
                'balance' => (int) $balance,
                'days_running' => $daysRunning,
                'achievement' => round($achievement, 2),
                'last_update' => $lastUpdate->format('Y-m-d')
            ];
        }

        // Aggregate by gl_number, nesting colors inside
        $aggregated = [];
        foreach ($data as $item) {
            $glKey = $item['gl_number'];
            if (!isset($aggregated[$glKey])) {
                $aggregated[$glKey] = [
                    'gl_number' => $item['gl_number'],
                    'order_qty' => $item['order_qty'], // Assuming same order_qty for all colors in the lot
                    'output_qty' => 0,
                    'balance' => 0,
                    'days_running' => $item['days_running'],
                    'achievement' => 0,
                    'last_update' => $item['last_update'],
                    'colors' => []
                ];
            } else {
                // If a GL has multiple lots with different order_qtys, we might want to take the max or sum
                // But typically gmt_qty is per GL. Let's take the max just to be safe if they differ.
                if ($item['order_qty'] > $aggregated[$glKey]['order_qty']) {
                    $aggregated[$glKey]['order_qty'] = $item['order_qty'];
                }
            }

            // Accumulate output
            $aggregated[$glKey]['output_qty'] += $item['output_qty'];
            
            if ($item['last_update'] > $aggregated[$glKey]['last_update']) {
                $aggregated[$glKey]['last_update'] = $item['last_update'];
            }
            if ($item['days_running'] > $aggregated[$glKey]['days_running']) {
                $aggregated[$glKey]['days_running'] = $item['days_running'];
            }
            
            // Push color details
            $aggregated[$glKey]['colors'][] = [
                'color' => $item['color'],
                'output_qty' => $item['output_qty'],
                'achievement' => $item['achievement']
            ];
        }

        // Final calculations for GL level
        foreach ($aggregated as &$gl) {
            $gl['balance'] = $gl['output_qty'] - $gl['order_qty'];
            $gl['achievement'] = $gl['order_qty'] > 0 
                ? round(($gl['output_qty'] / $gl['order_qty']) * 100, 2) 
                : 0;
        }

        return response()->json([
            'status' => 'success',
            'data' => array_values($aggregated)
        ]);
    }
}
