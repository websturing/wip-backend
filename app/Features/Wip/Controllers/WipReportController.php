<?php

namespace App\Features\Wip\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\Lot;
use App\Features\Production\Models\ProductionItemDetail;
use App\Features\Packing\Models\PackingItemDetail;
use App\Features\Wip\Models\WipExportQuantity;
use App\Features\Wip\Models\WipExportQuantityHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WipReportController extends Controller
{
    public function summary(Request $request)
    {
        // 1. Fetch ALL Lot Codes for filter options (metadata only)
        $allLotCodes = Lot::where('is_cancelled', false)
            ->select('id', 'lot_code')
            ->orderBy('lot_code')
            ->get();

        // 2. Determine which lots to show in the report
        $selectedGls = $request->get('selected_gls', []);
        
        $query = Lot::with(['glGroup.customer', 'exportQuantity'])
            ->where('is_cancelled', false);

        if (!empty($selectedGls)) {
            $query->whereIn('lot_code', $selectedGls);
        } else {
            // Default: Show 10 lots with newest production/packing activity
            $latestProducingLotIds = DB::table('production_items')
                ->select('lot_id', DB::raw('MAX(created_at) as act'))
                ->groupBy('lot_id');
            
            $latestPackingLotIds = DB::table('packing_items')
                ->select('lot_id', DB::raw('MAX(created_at) as act'))
                ->groupBy('lot_id');

            $topLotIds = DB::table(DB::raw("({$latestProducingLotIds->toSql()}) as prod"))
                ->mergeBindings($latestProducingLotIds)
                ->select('lot_id', 'act')
                ->union($latestPackingLotIds)
                ->orderBy('act', 'desc')
                ->limit(10)
                ->pluck('lot_id');

            if ($topLotIds->isNotEmpty()) {
                $query->whereIn('id', $topLotIds)
                    ->orderByRaw('FIELD(id, "' . $topLotIds->implode('","') . '")');
            } else {
                $query->latest('updated_at')->limit(10);
            }
        }
        
        $lots = $query->get();

        // 3. Fetch Aggregated Sewing Output
        $sewingSummaries = ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->join('lines', 'productions.line_id', '=', 'lines.id')
            ->whereIn('production_items.lot_id', $lots->pluck('id'))
            ->select(
                'production_items.lot_id',
                DB::raw('SUM(qty_output) as total_output'),
                DB::raw('GROUP_CONCAT(DISTINCT lines.location SEPARATOR ", ") as locations')
            )
            ->groupBy('production_items.lot_id')
            ->get()
            ->keyBy('lot_id');

        // 4. Fetch Aggregated Packing Output
        $packingSummaries = PackingItemDetail::join('packing_items', 'packing_item_details.packing_item_id', '=', 'packing_items.id')
            ->whereIn('packing_items.lot_id', $lots->pluck('id'))
            ->select('packing_items.lot_id', DB::raw('SUM(qty_output) as total_output'))
            ->groupBy('packing_items.lot_id')
            ->get()
            ->keyBy('lot_id');

        // 5. Fetch Cutting Output (ONLY for the lots being displayed - Using GL+Lot)
        $uniqueLotCodes = $lots->pluck('lot_code')->filter()->unique();
        $cuttingCache = [];
        
        if ($uniqueLotCodes->isNotEmpty()) {
            $responses = \Illuminate\Support\Facades\Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($uniqueLotCodes) {
                foreach ($uniqueLotCodes as $lotCode) {
                    $pool->as($lotCode)->timeout(5)->withoutVerifying()->get("http://cutting.glaindonesia.lan/api/summary-by-gl?gl_number={$lotCode}");
                }
            });

            foreach ($responses as $lotCode => $response) {
                if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                    $resData = $response->json();
                    if (($resData['status'] ?? 0) === 200) {
                        $target = $resData['data'] ?? [];
                        $totalCut = (int)($target['grand_total']['cut_qty'] ?? 0);
                        if ($totalCut === 0 && isset($target['summary_by_color'])) {
                            foreach ($target['summary_by_color'] as $color) {
                                $totalCut += (int)($color['total_qty'] ?? $color['qty'] ?? $color['total_cut'] ?? 0);
                            }
                        }
                        $cuttingCache[$lotCode] = $totalCut;
                    } else {
                        $cuttingCache[$lotCode] = 'E404';
                    }
                } else {
                    $cuttingCache[$lotCode] = 'E404';
                }
            }
        }
        
        $reportData = $lots->map(function ($lot) use ($sewingSummaries, $packingSummaries, $cuttingCache) {
            $sewing = $sewingSummaries->get($lot->id);
            $packing = $packingSummaries->get($lot->id);
            
            $cuttingAcc = $cuttingCache[$lot->lot_code] ?? 0;

            return [
                'lot_id' => $lot->id,
                'gl_lot' => $lot->lot_code,
                'customer' => $lot->glGroup->customer->name ?? '-',
                'brand' => $lot->brand ?? '-',
                'style_no' => $lot->style_no ?? '-',
                'product_type' => '-', 
                'ex_fty_date' => '-', 
                'fty' => $sewing->locations ?? '-',
                'gmt_delivery_date' => $lot->delivery_date ?? '-',
                'order_qty_dz' => (int) round($lot->gmt_qty / 12),
                'order_qty_pcs' => (int) $lot->gmt_qty,
                
                // CUTTING
                'cutting_acc_output' => $cuttingAcc, 
                
                // SEWING
                'sewing_acc_output' => (int) ($sewing->total_output ?? 0),
                
                // PACKING
                'packing_acc_output' => (int) ($packing->total_output ?? 0),

                // EXPORT
                'export_qty' => (int) ($lot->exportQuantity->qty ?? 0),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $reportData,
            'gl_options' => $allLotCodes->pluck('lot_code')
        ]);
    }

    public function balanceSummary(Request $request)
    {
        $validated = $request->validate([
            'lot_id' => 'required',
            'colors' => 'required|array',
        ]);

        $lot = Lot::with(['glGroup.customer'])->where('id', $validated['lot_id'])->orWhere('lot_code', $validated['lot_id'])->firstOrFail();
        $colors = $validated['colors'];

        // 1. Fetch ALL Raw Production Data for all colors
        // Note: Production table has BOTH qty_input and qty_output in the details
        $allData = ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->join('lines', 'productions.line_id', '=', 'lines.id')
            ->where('production_items.lot_id', $lot->id)
            ->whereIn('production_items.color', $colors)
            ->select(
                'production_items.color',
                'productions.production_date',
                'lines.name as line_name',
                'size_name',
                'qty_input',
                'qty_output'
            )
            ->get();

        // Check if I joined correctly. ProductionItemDetail has production_item_id.
        // Wait, ProductionItem has production_id.
        // My previous join in step 212 was:
        /*
        $allInputData = ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->join('lines', 'productions.line_id', '=', 'lines.id')
        */
        // That seems correct. Let me re-verify ProductionItem relationship.

        $sizes = $allData->pluck('size_name')->unique()->values()->toArray();
        $sizeOrder = ['XXS', 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', '6XL'];
        usort($sizes, function($a, $b) use ($sizeOrder) {
            $posA = array_search(strtoupper($a), $sizeOrder);
            $posB = array_search(strtoupper($b), $sizeOrder);
            if ($posA === false && $posB === false) return strcmp($a, $b);
            if ($posA === false) return 1;
            if ($posB === false) return -1;
            return $posA - $posB;
        });

        // Construct Separate Reports per Color
        $reports = [];
        foreach ($colors as $color) {
            $colorData = $allData->where('color', $color);

            // Group Input per color (qty_input > 0)
            $groupedInput = $colorData->where('qty_input', '>', 0)
                ->groupBy(function($item) {
                    return $item->production_date . '|' . $item->line_name;
                })->map(function($items, $key) use ($sizes) {
                    [$date, $line] = explode('|', $key);
                    $sizeMap = $items->groupBy('size_name')->map->sum('qty_input');
                    $row = [
                        'date' => date('d-M', strtotime($date)),
                        'line' => $line,
                        'sizes' => []
                    ];
                    $total = 0;
                    foreach ($sizes as $s) {
                        $val = (int) $sizeMap->get($s, 0);
                        $row['sizes'][$s] = $val;
                        $total += $val;
                    }
                    $row['total'] = $total;
                    return $row;
                })->values();

            // Group Output per color (qty_output > 0)
            $groupedOutput = $colorData->where('qty_output', '>', 0)
                ->groupBy(function($item) {
                    return $item->production_date . '|' . $item->line_name;
                })->map(function($items, $key) use ($sizes) {
                    [$date, $line] = explode('|', $key);
                    $sizeMap = $items->groupBy('size_name')->map->sum('qty_output');
                    $row = [
                        'date' => date('d-M', strtotime($date)),
                        'line' => $line,
                        'do_number' => '-', 
                        'sizes' => []
                    ];
                    $total = 0;
                    foreach ($sizes as $s) {
                        $val = (int) $sizeMap->get($s, 0);
                        $row['sizes'][$s] = $val;
                        $total += $val;
                    }
                    $row['total'] = $total;
                    return $row;
                })->values();

            $reports[] = [
                'color' => $color,
                'input' => $groupedInput,
                'output' => $groupedOutput
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'header' => [
                    'gl' => $lot->lot_code,
                    'style' => $lot->style_no,
                    'order_qty' => (int) $lot->gmt_qty,
                    'buyer' => $lot->glGroup->customer->name ?? '-',
                ],
                'sizes' => $sizes,
                'reports' => $reports
            ]
        ]);
    }

    public function getLotColors(Request $request)
    {
        $lotId = $request->get('lot_id');
        if (!$lotId) return response()->json(['status' => 'error', 'message' => 'lot_id required'], 400);

        $lot = Lot::where('id', $lotId)->orWhere('lot_code', $lotId)->firstOrFail();
        
        $productionColors = DB::table('production_items')->where('lot_id', $lot->id)->pluck('color');
        $packingColors = DB::table('packing_items')->where('lot_id', $lot->id)->pluck('color');
        
        $colors = $productionColors->merge($packingColors)->unique()->values();
        
        return response()->json(['status' => 'success', 'data' => $colors]);
    }

    public function storeExport(Request $request)
    {
        $request->validate([
            'lot_id' => 'required|exists:lots,id',
            'qty' => 'required|integer|min:0'
        ]);

        $user = $request->user() ?: \App\Models\User::first(); // Fallback to first user if not authenticated for now
        
        DB::beginTransaction();
        try {
            $export = WipExportQuantity::where('lot_id', $request->lot_id)->first();
            
            if ($export) {
                // Record History
                WipExportQuantityHistory::create([
                    'export_quantity_id' => $export->id,
                    'old_qty' => $export->qty,
                    'new_qty' => $request->qty,
                    'updated_by' => $user->id
                ]);

                $export->update([
                    'qty' => $request->qty,
                    'updated_by' => $user->id
                ]);
            } else {
                $export = WipExportQuantity::create([
                    'lot_id' => $request->lot_id,
                    'qty' => $request->qty,
                    'created_by' => $user->id
                ]);
            }
            
            DB::commit();
            return response()->json(['status' => 'success', 'data' => $export]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
