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
use App\Helpers\SizeHelper;

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
            ->where('production_items.section', 'inline')
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
                        
                        // Use body_only cut_qty if available, otherwise fallback
                        if (isset($target['grand_total']['body_only']['cut_qty'])) {
                            $totalCut = (int)$target['grand_total']['body_only']['cut_qty'];
                        } else {
                            $totalCut = (int)($target['grand_total']['cut_qty'] ?? 0);
                            if ($totalCut === 0 && isset($target['summary_by_color'])) {
                                foreach ($target['summary_by_color'] as $color) {
                                    $totalCut += (int)($color['cut_qty'] ?? $color['total_qty'] ?? $color['qty'] ?? $color['total_cut'] ?? 0);
                                }
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

        // 1. Fetch CUTTING API data for per-size breakdown
        // We try both the full lot_code and just the base GL number, and multiple endpoints
        $lotCode = $lot->lot_code;
        $baseGl = explode('-', $lotCode)[0] ?? $lotCode;
        $apiParams = array_unique([$lotCode, $baseGl]);
        
        $cuttingDataPerColor = [];
        $endpoints = [
            "http://cutting.glaindonesia.lan/api/summary-by-gl?gl_number=",
            "https://cutting.glaindonesia.lan/api/gl-number/summary-by-gl/"
        ];

        foreach ($apiParams as $param) {
            if (empty($param)) continue;
            foreach ($endpoints as $urlBase) {
                // Skip if we already got data for this lot/gl in a previous attempt (optimization)
                if (!empty($cuttingDataPerColor)) break;

                try {
                    $url = $urlBase . $param;
                    $response = Http::timeout(5)->withoutVerifying()->get($url);
                    
                    if ($response->successful()) {
                        $resData = $response->json();
                        // Some endpoints return data in 'data', some directly in root
                        $root = $resData['data'] ?? $resData;
                        $summaryByColor = $root['summary_by_color'] ?? [];
                        
                        if (!empty($summaryByColor)) {
                            foreach ($summaryByColor as $colorInfo) {
                                $cName = strtoupper(trim($colorInfo['color'] ?? ''));
                                // Broad detection of size keys
                                $sizesBreakdown = $colorInfo['summary_by_size'] ?? 
                                                 $colorInfo['details'] ?? 
                                                 $colorInfo['sizes'] ?? 
                                                 $colorInfo['size_breakdown'] ?? 
                                                 $colorInfo['ratio'] ?? [];
                                
                                $map = [];
                                foreach ($sizesBreakdown as $sb) {
                                    $sName = strtoupper(trim($sb['size'] ?? $sb['size_name'] ?? $sb['size_label'] ?? ''));
                                    $q = (int)($sb['cut_qty'] ?? $sb['qty'] ?? $sb['total_cut'] ?? $sb['total_qty'] ?? 0);
                                    if ($sName) $map[$sName] = $q;
                                }
                                if (!empty($map)) {
                                    $cuttingDataPerColor[$cName] = $map;
                                }
                            }
                        }
                    }
                } catch (\Exception $e) { }
            }
        }

        // 1. Fetch ALL Raw Production Data for all colors
        // Note: Production table has BOTH qty_input and qty_output in the details
        $allData = ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->join('lines', 'productions.line_id', '=', 'lines.id')
            ->where('production_items.lot_id', $lot->id)
            ->whereIn('production_items.color', $colors)
            ->select(
                'production_items.color',
                'production_items.section',
                'productions.production_date',
                'lines.name as line_name',
                'size_name',
                'qty_input',
                'qty_output'
            )
            ->get();

        $sizes = $allData->pluck('size_name')->unique()->values()->toArray();

        // Merge with sizes from Cutting API
        foreach ($cuttingDataPerColor as $cMap) {
            foreach (array_keys($cMap) as $s) {
                if (!in_array($s, $sizes)) $sizes[] = $s;
            }
        }

        $sizes = SizeHelper::sortArray($sizes);

        // Construct Separate Reports per Color
        $reports = [];
        foreach ($colors as $color) {
            $colorData = $allData->where('color', $color);

            // Find Matching color in Cutting Data (Fuzzy match for "NEO NATURAL" etc.)
            $targetColor = strtoupper(trim($color));
            $cutMap = $cuttingDataPerColor[$targetColor] ?? null;

            if (!$cutMap) {
                $cleanTarget = str_replace([' ', '-', '_'], '', $targetColor);
                foreach ($cuttingDataPerColor as $apiColor => $map) {
                    $cleanApi = str_replace([' ', '-', '_'], '', $apiColor);
                    if ($cleanTarget === $cleanApi) {
                        $cutMap = $map;
                        break;
                    }
                }
            }
            $cutMap = $cutMap ?? [];

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
                ->where('section', 'inline')
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

            // Prepare per-size cutting row
            $cuttingRow = [
                'sizes' => [],
                'total' => 0
            ];
            foreach ($sizes as $s) {
                $val = (int) ($cutMap[strtoupper($s)] ?? 0);
                $cuttingRow['sizes'][$s] = $val;
                $cuttingRow['total'] += $val;
            }

            $reports[] = [
                'color' => $color,
                'cutting_qty' => $cuttingRow,
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
