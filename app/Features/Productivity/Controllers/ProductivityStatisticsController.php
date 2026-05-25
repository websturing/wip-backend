<?php

namespace App\Features\Productivity\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Reference\Models\Lot;
use App\Features\Production\Models\ProductionItemDetail;
use App\Features\Production\Models\ProductionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductivityStatisticsController extends Controller
{
            public function detailedStatistics(Request $request)
    {
        $lots = Lot::where('is_cancelled', false)->with('glGroup.customer')->get();

        $query = ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->select(
                'production_items.lot_id',
                'production_items.color',
                'production_item_details.size_name',
                DB::raw('SUM(qty_input) as input_qty'),
                DB::raw('SUM(qty_output) as output_qty'),
                DB::raw('MIN(productions.production_date) as start_date'),
                DB::raw('MAX(productions.production_date) as last_update')
            );
            
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('productions.production_date', [$request->start_date, $request->end_date]);
        }

        $outputs = $query->groupBy('production_items.lot_id', 'production_items.color', 'production_item_details.size_name')
            ->get();

        $data = [];

        foreach ($outputs as $out) {
            $lot = $lots->firstWhere('id', $out->lot_id);
            if (!$lot) continue;

            $colorName = trim($out->color);
            $baseName = $colorName;
            $type = 'other';
            if (preg_match('/(.*?)\s*\((TOP|PANT|PANTS)\)$/i', $colorName, $matches)) {
                $baseName = trim($matches[1]);
                $typeMatch = strtoupper($matches[2]);
                $type = $typeMatch === 'TOP' ? 'top' : 'pant';
            }
            
            $size = trim($out->size_name);
            if ($size && strtoupper($size) !== 'TOTAL') {
                $baseName .= ' - ' . $size;
                $displayColor = $colorName . ' - ' . $size;
            } else {
                $displayColor = $colorName;
            }

            $orderQty = (int) $out->input_qty;
            $balance = $out->output_qty - $orderQty;
            
            $startDate = Carbon::parse($out->start_date);
            $lastUpdate = Carbon::parse($out->last_update);
            $daysRunning = $startDate->diffInDays($lastUpdate) + 1;

            $achievement = $orderQty > 0 ? ($out->output_qty / $orderQty) * 100 : 0;

            $data[] = [
                'gl_number' => $lot->lot_code,
                'color' => $displayColor,
                'base_name' => $baseName,
                'type' => $type,
                'order_qty' => $orderQty,
                'output_qty' => (int) $out->output_qty,
                'balance' => (int) $balance,
                'days_running' => $daysRunning,
                'achievement' => round($achievement, 2),
                'last_update' => $lastUpdate->format('Y-m-d')
            ];
        }

        $aggregated = [];
        foreach ($data as $item) {
            $glKey = $item['gl_number'];
            if (!isset($aggregated[$glKey])) {
                $aggregated[$glKey] = [
                    'gl_number' => $item['gl_number'],
                    'order_qty' => 0, 
                    'output_qty' => 0,
                    'balance' => 0,
                    'days_running' => $item['days_running'],
                    'achievement' => 0,
                    'last_update' => $item['last_update'],
                    'is_set_item' => false,
                    'colors' => [],
                    'color_parts' => []
                ];
            }
            
            if ($item['last_update'] > $aggregated[$glKey]['last_update']) {
                $aggregated[$glKey]['last_update'] = $item['last_update'];
            }
            if ($item['days_running'] > $aggregated[$glKey]['days_running']) {
                $aggregated[$glKey]['days_running'] = $item['days_running'];
            }
            
            $aggregated[$glKey]['colors'][] = [
                'color' => $item['color'],
                'order_qty' => $item['order_qty'],
                'output_qty' => $item['output_qty'],
                'balance' => $item['balance'],
                'achievement' => $item['achievement']
            ];

            $baseName = $item['base_name'];
            $type = $item['type'];

            if (!isset($aggregated[$glKey]['color_parts'][$baseName])) {
                $aggregated[$glKey]['color_parts'][$baseName] = [
                    'top_input' => 0, 'pant_input' => 0, 'other_input' => 0,
                    'top_output' => 0, 'pant_output' => 0, 'other_output' => 0,
                    'has_set' => false
                ];
            }
            
            $aggregated[$glKey]['color_parts'][$baseName][$type . '_input'] += $item['order_qty'];
            $aggregated[$glKey]['color_parts'][$baseName][$type . '_output'] += $item['output_qty'];
            
            if ($type !== 'other') {
                $aggregated[$glKey]['color_parts'][$baseName]['has_set'] = true;
                $aggregated[$glKey]['is_set_item'] = true;
            }
        }

        foreach ($aggregated as &$gl) {
            $totalOutput = 0;
            $totalInput = 0;
            foreach ($gl['color_parts'] as $baseName => $parts) {
                if ($parts['has_set']) {
                    $totalInput += min($parts['top_input'], $parts['pant_input']);
                    $totalInput += $parts['other_input'];
                    
                    $totalOutput += min($parts['top_output'], $parts['pant_output']);
                    $totalOutput += $parts['other_output'];
                } else {
                    $totalInput += $parts['other_input'];
                    $totalOutput += $parts['other_output'];
                }
            }
            
            $gl['order_qty'] = $totalInput;
            $gl['output_qty'] = $totalOutput;
            $gl['balance'] = $gl['output_qty'] - $gl['order_qty'];
            $gl['achievement'] = $gl['order_qty'] > 0 
                ? round(($gl['output_qty'] / $gl['order_qty']) * 100, 2) 
                : 0;
                
            unset($gl['color_parts']); 
        }

        return response()->json([
            'status' => 'success',
            'data' => array_values($aggregated)
        ]);
    }

    private function getOutputSewingReportData(Request $request)
    {
        $lots = Lot::where('is_cancelled', false)->with('glGroup.customer')->get();

        $query = ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->where('production_items.section', 'inline')
            ->select(
                'production_items.lot_id',
                'production_items.color',
                'production_item_details.size_name',
                DB::raw('SUM(qty_input) as input_qty'),
                DB::raw('SUM(qty_output) as output_qty'),
                DB::raw('MIN(productions.production_date) as start_date'),
                DB::raw('MAX(productions.production_date) as last_update')
            );
            
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('productions.production_date', [$request->start_date, $request->end_date]);
        }

        $outputs = $query->groupBy('production_items.lot_id', 'production_items.color', 'production_item_details.size_name')
            ->get();

        $data = [];
        foreach ($outputs as $out) {
            $lot = $lots->firstWhere('id', $out->lot_id);
            if (!$lot) continue;

            $colorName = trim($out->color);
            $size = trim($out->size_name);
            if ($size && strtoupper($size) !== 'TOTAL') {
                $displayColor = $colorName . ' - ' . $size;
            } else {
                $displayColor = $colorName;
            }

            $orderQty = (int) $out->input_qty;
            $outputQty = (int) $out->output_qty;
            $sam = $lot->sam ?: 0;
            $minutes = $outputQty * $sam;
            
            $lastUpdate = Carbon::parse($out->last_update)->format('Y-m-d');
            $shipDate = $lot->delivery_date ? Carbon::parse($lot->delivery_date)->format('Y-m-d') : '';

            $data[] = [
                'date' => $lastUpdate,
                'gl_lot' => $lot->lot_code,
                'style' => $lot->style_no,
                'input_qty' => $orderQty,
                'output_qty' => $outputQty,
                'color' => $displayColor,
                'ship_date' => $shipDate,
                'sam' => $sam,
                'minutes' => $minutes
            ];
        }
        
        $summary = [];
        foreach ($data as $item) {
            $glLot = $item['gl_lot'];
            if (!isset($summary[$glLot])) {
                $summary[$glLot] = [
                    'gl_lot' => $glLot,
                    'style' => $item['style'],
                    'input_qty' => 0,
                    'output_qty' => 0,
                    'minutes' => 0,
                ];
            }
            $summary[$glLot]['input_qty'] += $item['input_qty'];
            $summary[$glLot]['output_qty'] += $item['output_qty'];
            $summary[$glLot]['minutes'] += $item['minutes'];
        }

        return [
            'detailed' => $data,
            'summary' => array_values($summary)
        ];
    }

    public function outputSewingReport(Request $request)
    {
        $reportData = $this->getOutputSewingReportData($request);
        return response()->json([
            'status' => 'success',
            'data' => $reportData['detailed'],
            'summary' => $reportData['summary']
        ]);
    }

    public function exportOutputSewingReport(Request $request)
    {
        $reportData = $this->getOutputSewingReportData($request);
        $detailedData = $reportData['detailed'];
        $summaryData = $reportData['summary'];

        $spreadsheet = new Spreadsheet();
        
        // --- Sheet 1: Detailed ---
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Detailed View');

        $headers1 = ['Date', 'GL-LOT', 'Style', 'Input Qty', 'Output Qty', 'Color', 'Ship Date', 'SAM', 'Minutes'];
        $sheet1->fromArray([$headers1], NULL, 'A1');
        
        $sheet1->getStyle('A1:I1')->getFont()->setBold(true);

        $row = 2;
        foreach ($detailedData as $item) {
            $sheet1->setCellValue('A' . $row, $item['date']);
            $sheet1->setCellValue('B' . $row, $item['gl_lot']);
            $sheet1->setCellValue('C' . $row, $item['style']);
            $sheet1->setCellValue('D' . $row, $item['input_qty']);
            $sheet1->setCellValue('E' . $row, $item['output_qty']);
            $sheet1->setCellValue('F' . $row, $item['color']);
            $sheet1->setCellValue('G' . $row, $item['ship_date']);
            $sheet1->setCellValue('H' . $row, $item['sam']);
            $sheet1->setCellValue('I' . $row, $item['minutes']);
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        // --- Sheet 2: Summary ---
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('GL Summary');
        
        $headers2 = ['GL-LOT', 'Style', 'Total Input Qty', 'Total Output Qty', 'Total Minutes'];
        $sheet2->fromArray([$headers2], NULL, 'A1');
        $sheet2->getStyle('A1:E1')->getFont()->setBold(true);

        $row = 2;
        foreach ($summaryData as $item) {
            $sheet2->setCellValue('A' . $row, $item['gl_lot']);
            $sheet2->setCellValue('B' . $row, $item['style']);
            $sheet2->setCellValue('C' . $row, $item['input_qty']);
            $sheet2->setCellValue('D' . $row, $item['output_qty']);
            $sheet2->setCellValue('E' . $row, $item['minutes']);
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $fileName = 'Output_Sewing_Report_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
