<?php

namespace App\Features\Productivity\Services;

use App\Features\Productivity\Models\Productivity;
use App\Features\Production\Models\Production;
use App\Features\Reference\Models\Lot;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductivityExportService
{
    public function export($productivityId)
    {
        $productivity = Productivity::with(['line', 'lots.glGroup.customer'])->findOrFail($productivityId);
        return $this->generateFile([$productivity], 'Productivity_' . $productivity->line->name . '_' . $productivity->date);
    }

    public function exportByDate($date, $lineIds = null, $type = 'productivity')
    {
        $query = Productivity::with(['line', 'lots.glGroup.customer'])
            ->whereDate('date', $date);

        if (!empty($lineIds)) {
            $query->whereIn('line_id', $lineIds);
        }

        $productivities = $query->get();

        // Natural Sort by Line Name
        $productivities = $productivities->sort(function($a, $b) {
            return strnatcasecmp($a->line->name ?? '', $b->line->name ?? '');
        });
        
        if ($type === 'sewing_output') {
            return $this->generateSewingOutputFile($productivities, 'Sewing_Output_' . $date, $date);
        }

        return $this->generateFile($productivities, 'Daily_Productivity_' . $date);
    }

    private function generateFile($productivities, $fileName)
    {
        $allLineIds = $productivities->pluck('line_id')->unique();
        $maxDate = $productivities->max('date');

        $productionData = Production::whereIn('line_id', $allLineIds)
            ->whereDate('production_date', '<=', $maxDate)
            ->with(['items.details'])
            ->get();

        $uniqueLotCodes = $productivities->flatMap->lots->pluck('lot_code')->unique();
        $cuttingCache = [];
        // ... (cutting API logic remains same)
        if ($uniqueLotCodes->isNotEmpty()) {
            $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($uniqueLotCodes) {
                foreach ($uniqueLotCodes as $lotCode) {
                    $pool->as($lotCode)->timeout(5)->withoutVerifying()->get("http://cutting.glaindonesia.lan/api/summary-by-gl?gl_number={$lotCode}");
                }
            });

            foreach ($responses as $lotCode => $response) {
                if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                    $resData = $response->json();
                    if (($resData['status'] ?? 0) === 200) {
                        $target = $resData['data'] ?? [];
                        
                        // Use body_only order_qty if available, otherwise fallback
                        if (isset($target['grand_total']['body_only']['order_qty'])) {
                            $orderQty = (int)$target['grand_total']['body_only']['order_qty'];
                        } else {
                            $orderQty = (int)($target['grand_total']['order_qty'] ?? $target['grand_total']['cut_qty'] ?? 0);
                        }
                        
                        $cuttingCache[$lotCode] = $orderQty;
                    }
                }
            }
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        // Group by Line Prefix (A, B, C...)
        $groupedBySheet = $productivities->groupBy(function($p) {
            return substr(strtoupper($p->line->name ?? 'OTHER'), 0, 1);
        });

        foreach ($groupedBySheet as $prefix => $sheetItems) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle('Group ' . $prefix);
            
            $row = 1;
            $allTotals = ['sewers' => 0, 'manpower' => 0, 'hours' => 0, 'target' => 0, 'output' => 0, 'lines_count' => 0];

            foreach ($sheetItems as $productivity) {
                $groupedLots = $this->groupLots($productivity->lots);
                $allTotals['lines_count']++;

                foreach ($groupedLots as $lotGroup) {
                    $firstLot = $lotGroup[0];
                    $smv = (float)($firstLot->pivot->smv ?? $productivity->smv ?? 0);
                    $mp = (float)($productivity->manpower ?? $firstLot->pivot->manpower ?? 0);
                    $mg = (float)($productivity->sewer ?? $firstLot->pivot->sewer ?? 0);
                    $wh = (float)($firstLot->pivot->working_hour ?? $productivity->working_hour ?? 8);
                    $target = $smv > 0 ? floor(($mp + $mg) * $wh * 60 / $smv) : 0;
                    
                    // NEW: Use MAX output for combined lots (Daily only for grand totals)
                    // NEW: Use SUM output for combined lots
                    $pData = $productionData->filter(fn($p) => 
                        $p->line_id === $productivity->line_id && 
                        $p->production_date->format('Y-m-d') === $productivity->date->format('Y-m-d')
                    );
                    $section = strtoupper($firstLot->pivot->section ?? 'ALL');
                    $lotOutputs = collect($lotGroup)->map(function ($l) use ($pData, $section) {
                        return collect($pData)
                            ->flatMap->items
                            ->filter(fn($pi) => (string)$pi->lot_id === (string)$l->id && strtoupper($pi->section ?? 'ALL') === $section)
                            ->flatMap->details->sum('qty_output');
                    })->toArray();
                    $output = !empty($lotOutputs) ? array_sum($lotOutputs) : 0;

                    if ($lotGroup === $groupedLots[0]) {
                        $allTotals['sewers'] += $mg;
                        $allTotals['manpower'] += $mp;
                    }
                    
                    $allTotals['hours'] += (($mg + $mp) * $wh);
                    $allTotals['target'] += $target;
                    $allTotals['output'] += $output;

                    $this->drawStyleBlock($sheet, 'B', $row, $lotGroup, $productivity, $productionData, $cuttingCache);
                    $row += 9; // Reverted to 9 after removing Offline Output
                }
            }
            $this->drawGrandTotalBlock($sheet, 'B', $row, $allTotals, $productivities->pluck('date')->first());
        }

        $writer = new Xlsx($spreadsheet);
        $finalName = $fileName . '.xlsx';
        $tempPath = storage_path('app/public/' . $finalName);
        $writer->save($tempPath);

        return $tempPath;
    }

    private function drawStyleBlock($sheet, $startCol, $row, $lotGroup, $productivity, $productionData, $cuttingCache)
    {
        $firstLot = $lotGroup[0];
        $firstLot->pivot->load('media');

        $c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCol) - 1);
        $c2 = $startCol;
        $c3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 2);
        $c4 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 3);
        $c5 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 4);
        $c6 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 5);
        $c7 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 6);

        // Aggregation for Group
        $combinedLots = collect($lotGroup)->map(fn($l) => ltrim($l->lot_code, '0'))->join(' + ');
        $combinedGLs = collect($lotGroup)->map(fn($l) => $l->glGroup->gl_number)->unique()->join(' / ');
        $lotIds = collect($lotGroup)->pluck('id')->toArray();
        $smv = (float)($firstLot->pivot->smv ?? $productivity->smv ?? 0);
        $mp = (float)($productivity->manpower ?? $firstLot->pivot->manpower ?? 0);
        $mg = (float)($productivity->sewer ?? $firstLot->pivot->sewer ?? 0);
        $wh = (float)($firstLot->pivot->working_hour ?? $productivity->working_hour ?? 8);
        $targetPlanTotal = collect($lotGroup)->sum(fn($l) => $l->pivot->target_plan ?? 0);

        // Styling Defaults
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THICK]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']]
        ];

        $labelStyle = [
            'font' => ['bold' => true, 'size' => 8],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_DOTTED]]
        ];

        $dataStyle = [
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        $sheet->mergeCells("{$c1}{$row}:{$c1}" . ($row + 5)); 
        $sheet->setCellValue("{$c1}{$row}", $productivity->line->name);
        $sheet->getStyle("{$c1}{$row}")->applyFromArray($headerStyle);
        $sheet->getStyle("{$c1}{$row}")->getAlignment()->setTextRotation(90);

        $sheet->mergeCells("{$c2}{$row}:{$c2}" . ($row + 1));
        $sheet->setCellValue("{$c2}{$row}", "Sewer/Helper | 车工/外勤工");
        $sheet->getStyle("{$c2}{$row}")->applyFromArray($labelStyle);

        $sheet->mergeCells("{$c3}{$row}:{$c4}{$row}");
        $sheet->setCellValue("{$c3}{$row}", "TARGET OUTPUT: " . number_format($targetPlanTotal, 0, ',', '.'));
        $sheet->getStyle("{$c3}{$row}")->applyFromArray($headerStyle);
        $sheet->getStyle("{$c3}{$row}")->getFill()->getStartColor()->setRGB('FFEB9C');
        
        // --- ROW 2 (MP & MG) ---
        $sheet->setCellValue("{$c3}" . ($row + 1), $mp);
        $sheet->setCellValue("{$c4}" . ($row + 1), $mg);
        $sheet->getStyle("{$c3}" . ($row + 1))->applyFromArray($dataStyle);
        $sheet->getStyle("{$c4}" . ($row + 1))->applyFromArray($dataStyle);

        // --- ROW 3 (Working Hour) ---
        $sheet->setCellValue("{$c2}" . ($row + 2), "working hour | 工资工时");
        $sheet->getStyle("{$c2}" . ($row + 2))->applyFromArray($labelStyle);
        
        $sheet->mergeCells("{$c3}" . ($row + 2) . ":{$c4}" . ($row + 2));
        $sheet->setCellValue("{$c3}" . ($row + 2), number_format($wh, 1, ',', '.'));
        $sheet->getStyle("{$c3}" . ($row + 2))->applyFromArray($dataStyle);

        // --- ROW 4 (Actual Hour) ---
        $actualHour = ($mp + $mg) * $wh;
        $sheet->setCellValue("{$c2}" . ($row + 3), "Actual Hour | 当天工资工时");
        $sheet->getStyle("{$c2}" . ($row + 3))->applyFromArray($labelStyle);
        
        $sheet->mergeCells("{$c3}" . ($row + 3) . ":{$c4}" . ($row + 3));
        $sheet->setCellValue("{$c3}" . ($row + 3), number_format($actualHour, 1, ',', '.'));
        $sheet->getStyle("{$c3}" . ($row + 3))->applyFromArray($dataStyle);

        // --- ROW 5 (Factory IE / SMV) ---
        $sheet->setCellValue("{$c2}" . ($row + 4), "Factory IE(工厂 IE)");
        $sheet->getStyle("{$c2}" . ($row + 4))->applyFromArray($labelStyle);
        
        $sheet->mergeCells("{$c3}" . ($row + 4) . ":{$c4}" . ($row + 4));
        $sheet->setCellValue("{$c3}" . ($row + 4), number_format($smv, 2, ',', '.') . " Min");
        $sheet->getStyle("{$c3}" . ($row + 4))->applyFromArray($dataStyle);
        $sheet->getStyle("{$c3}" . ($row + 4))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('92D050');

        // --- ROW 6 (China IE) ---
        $sheet->setCellValue("{$c2}" . ($row + 5), "China IE(中国 IE)");
        $sheet->getStyle("{$c2}" . ($row + 5))->applyFromArray($labelStyle);
        $sheet->mergeCells("{$c3}" . ($row + 5) . ":{$c4}" . ($row + 5));
        $sheet->setCellValue("{$c3}" . ($row + 5), "0,00 Min");
        $sheet->getStyle("{$c3}" . ($row + 5))->applyFromArray($dataStyle);

        // --- CENTER COLUMN (Style / Lot / Image) ---
        $sheet->mergeCells("{$c5}{$row}:{$c5}" . ($row + 1));
        $sheet->setCellValue("{$c5}{$row}", $combinedGLs);
        $sheet->getStyle("{$c5}{$row}")->applyFromArray($headerStyle);
        $sheet->getStyle("{$c5}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');

        $section = strtoupper($firstLot->pivot->section ?? 'ALL');
        $sheet->mergeCells("{$c5}" . ($row + 2) . ":{$c5}" . ($row + 2));
        $sheet->setCellValue("{$c5}" . ($row + 2), "{$combinedLots} - ({$section})");
        $sheet->getStyle("{$c5}" . ($row + 2))->applyFromArray($labelStyle);

        // Image Placeholder
        $sheet->mergeCells("{$c5}" . ($row + 3) . ":{$c5}" . ($row + 5));
        if ($firstLot->pivot->media && $firstLot->pivot->media->file_path) {
            $this->addDrawing($sheet, $c5, $row + 3, $firstLot->pivot->media->file_path);
        }

        // --- RIGHT COLUMN (Metrics) ---
        $sheet->setCellValue("{$c6}{$row}", "Order Qty");
        $sheet->getStyle("{$c6}{$row}")->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $orderQty = collect($lotGroup)->sum(fn($l) => $cuttingCache[$l->lot_code] ?? 0);
        $sheet->setCellValue("{$c7}{$row}", $orderQty);
        $sheet->getStyle("{$c7}{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("{$c7}{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $sheet->setCellValue("{$c6}" . ($row + 1), "Daily Target");
        $sheet->getStyle("{$c6}" . ($row + 1))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $target = $smv > 0 ? floor(($mp + $mg) * $wh * 60 / $smv) : 0;
        $sheet->setCellValue("{$c7}" . ($row + 1), $target);
        $sheet->getStyle("{$c7}" . ($row + 1))->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("{$c7}" . ($row + 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->setCellValue("{$c6}" . ($row + 2), "Daily Output");
        $sheet->getStyle("{$c6}" . ($row + 2))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $productivityDate = $productivity->date->format('Y-m-d');
        $lineProduction = $productionData->where('line_id', $productivity->line_id);
        
        // Daily Output calculation
        $dailyItems = $lineProduction->filter(fn($p) => $p->production_date->format('Y-m-d') === $productivityDate)->flatMap->items;
        $dailyLotOutputs = [];
        foreach ($lotGroup as $l) {
            $dailyLotOutputs[] = $dailyItems->filter(fn($i) => (string)$i->lot_id === (string)$l->id)
                ->flatMap->details->sum('qty_output');
        }
        $dailyOutput = !empty($dailyLotOutputs) ? max($dailyLotOutputs) : 0;

        // Total Output (Archived) calculation
        $totalItems = $lineProduction->filter(fn($p) => $p->production_date->format('Y-m-d') <= $productivityDate)->flatMap->items;
        $totalLotOutputs = [];
        foreach ($lotGroup as $l) {
            $totalLotOutputs[] = $totalItems->filter(fn($i) => (string)$i->lot_id === (string)$l->id)
                ->flatMap->details->sum('qty_output');
        }
        $totalOutput = !empty($totalLotOutputs) ? max($totalLotOutputs) : 0;

        $sheet->setCellValue("{$c7}" . ($row + 2), $dailyOutput);
        $sheet->getStyle("{$c7}" . ($row + 2))->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("{$c7}" . ($row + 2))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->setCellValue("{$c6}" . ($row + 3), "Archived");
        $sheet->getStyle("{$c6}" . ($row + 3))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 3))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->setCellValue("{$c7}" . ($row + 3), $totalOutput);
        $sheet->getStyle("{$c7}" . ($row + 3))->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("{$c7}" . ($row + 3))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->setCellValue("{$c6}" . ($row + 4), "% Achieved");
        $sheet->getStyle("{$c6}" . ($row + 4))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $achieved = $target > 0 ? ($dailyOutput / $target) : 0;
        $cellAch = $c7 . ($row + 4);
        $sheet->setCellValue($cellAch, $achieved);
        $sheet->getStyle($cellAch)->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle($cellAch)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
        $sheet->getStyle($cellAch)->getFont()->setBold(true);
        $sheet->getStyle($cellAch)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->setCellValue("{$c6}" . ($row + 5), "Balance");
        $sheet->getStyle("{$c6}" . ($row + 5))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        // Formula: Order Qty - Total Output
        $sheet->setCellValue("{$c7}" . ($row + 5), $orderQty - $totalOutput);
        $sheet->getStyle("{$c7}" . ($row + 5))->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("{$c7}" . ($row + 5))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // EXTRA DECORATION: Orange bottom for Section Label
        $sheet->mergeCells("{$c5}" . ($row+7) . ":{$c5}" . ($row+7));
        $section = strtoupper($firstLot->pivot->section ?? 'ALL');
        $sheet->setCellValue("{$c5}" . ($row+7), $section);
        $sheet->getStyle("{$c5}" . ($row+7))->applyFromArray($headerStyle);
        $sheet->getStyle("{$c5}" . ($row+7))->getFill()->getStartColor()->setRGB('FFC000');

        // Row height adjustment
        for ($i = 0; $i <= 7; $i++) {
            $sheet->getRowDimension($row + $i)->setRowHeight(25);
        }

        // Set Column widths
        $sheet->getColumnDimension($c1)->setWidth(5);
        $sheet->getColumnDimension($c2)->setWidth(25);
        $sheet->getColumnDimension($c3)->setWidth(15);
        $sheet->getColumnDimension($c4)->setWidth(15);
        $sheet->getColumnDimension($c5)->setWidth(25);
        $sheet->getColumnDimension($c6)->setWidth(15);
        $sheet->getColumnDimension($c7)->setWidth(15);
    }

    private function generateSewingOutputFile($productivities, $fileName, $date)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sewing Output Summary');

        // Header Title
        $sheet->mergeCells('A1:O1');
        $sheet->setCellValue('A1', 'DAILY SEWING OUTPUT SUMMARY - ' . $date);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = [
            'Line', 'LOT', 
            'MP Plan', 'Target Plan', 'MP Actual', 'Target Act', 
            'Output Daily', 'Last Step', 
            'DIFF Do Vs Targ', '% T.Act', 
            'Diff Do vs Plan', '% T.Plan', 
            'Remarks'
        ];

        $col = 'A';
        $row = 3;
        foreach ($headers as $h) {
            $sheet->setCellValue($col . $row, $h);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
            $sheet->getStyle($col . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $col++;
        }

        $row = 4;
        
        // Categorize and Sort productivities
        $categories = ['A1-8', 'A9-16', 'A1-8 NS', 'A9-16 NS', 'OTHER'];
        $groupedProd = [];
        foreach ($categories as $cat) $groupedProd[$cat] = [];

        foreach ($productivities as $p) {
            $cat = $this->getLineCategory($p->line->name);
            $groupedProd[$cat][] = $p;
        }

        $dayGrand = ['mpPln' => 0, 'tgtPln' => 0, 'mpAct' => 0, 'tgtAct' => 0, 'out' => 0];
        $nsGrand = ['mpPln' => 0, 'tgtPln' => 0, 'mpAct' => 0, 'tgtAct' => 0, 'out' => 0];

        // Sorting within groups naturally
        foreach ($groupedProd as $cat => &$items) {
            usort($items, function($a, $b) {
                return strnatcasecmp($a->line->name, $b->line->name);
            });
        }

        // Fetch production data for output calculations
        $allLineIds = $productivities->pluck('line_id')->unique();
        $productionData = Production::whereIn('line_id', $allLineIds)
            ->whereDate('production_date', $date)
            ->with(['items.details'])
            ->get();

        foreach ($categories as $catName) {
            $items = $groupedProd[$catName];
            if (empty($items)) continue;

            // Group Header
            $sheet->mergeCells("A{$row}:M{$row}");
            $sheet->setCellValue("A{$row}", "GROUP " . $catName);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EFEFEF');
            $row++;

            $groupTPln = 0; $groupTAct = 0; $groupOut = 0;
            $groupMPPln = 0; $groupMPAct = 0;

            foreach ($items as $p) {
                $groupedLots = $this->groupLots($p->lots);

                foreach ($groupedLots as $lotGroup) {
                    $firstLot = $lotGroup[0];
                    
                    $combinedLots = collect($lotGroup)->map(fn($l) => ltrim($l->lot_code, '0'))->join(' + ');
                    $lotIds = collect($lotGroup)->pluck('id')->toArray();
                    $smv = (float)($firstLot->pivot->smv ?? $p->smv ?? 0);
                    $mpAct = (float)($p->manpower ?? $firstLot->pivot->manpower ?? 0) + (float)($p->sewer ?? $firstLot->pivot->sewer ?? 0);
                    $mpPln = (float)($p->plan_manpower ?? $firstLot->pivot->plan_manpower ?? 0);
                    $tgtPln = collect($lotGroup)->sum(fn($l) => $l->pivot->target_plan ?? 0);
                    $wh = (float)($firstLot->pivot->working_hour ?? $p->working_hour ?? 8);
                    $tgtAct = $smv > 0 ? floor(($mpAct * $wh * 60) / $smv) : 0;

                    $lpItems = $productionData->where('line_id', $p->line_id)->flatMap->items;
                    
                    // Use SUM Output for Sewing Summary
                    $lotOutputs = [];
                    foreach ($lotGroup as $l) {
                        $lotOutputs[] = $lpItems->filter(fn($i) => (string)$i->lot_id === (string)$l->id)
                            ->flatMap->details->sum('qty_output');
                    }
                    $output = !empty($lotOutputs) ? array_sum($lotOutputs) : 0;
                    
                    $lastStep = collect($lotGroup)->max(fn($l) => $l->pivot->last_step ?? 0);

                    $section = strtoupper($firstLot->pivot->section ?? 'ALL');
                    $lotWithSection = "{$combinedLots} - ({$section})";

                    $sheet->setCellValue('A' . $row, $p->line->name);
                    $sheet->setCellValue('B' . $row, $lotWithSection);
                    $sheet->setCellValue('C' . $row, $mpPln);
                    $sheet->setCellValue('D' . $row, $tgtPln);
                    $sheet->setCellValue('E' . $row, $mpAct);
                    $sheet->setCellValue('F' . $row, $tgtAct);
                    $sheet->setCellValue('G' . $row, $output);
                    $sheet->setCellValue('H' . $row, $lastStep);
                    $sheet->setCellValue('I' . $row, $output - $tgtAct);
                    $sheet->setCellValue('J' . $row, $tgtAct > 0 ? ($output / $tgtAct) : 0);
                    $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('0.00%');
                    $sheet->setCellValue('K' . $row, $output - $tgtPln);
                    $sheet->setCellValue('L' . $row, $tgtPln > 0 ? ($output / $tgtPln) : 0);
                    $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('0.00%');
                    $sheet->setCellValue('M' . $row, ''); // Remarks

                    $sheet->getStyle('A' . $row . ':M' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    
                    $groupTPln += $tgtPln; 
                    $groupTAct += $tgtAct; 
                    $groupOut += $output;
                    $groupMPPln += $mpPln;
                    $groupMPAct += $mpAct;
                    
                    $row++;
                }
            }

            // Group Subtotal
            $sheet->setCellValue('A' . $row, "TOTAL " . $catName);
            $sheet->setCellValue('C' . $row, $groupMPPln);
            $sheet->setCellValue('D' . $row, $groupTPln);
            $sheet->setCellValue('E' . $row, $groupMPAct);
            $sheet->setCellValue('F' . $row, $groupTAct);
            $sheet->setCellValue('G' . $row, $groupOut);
            
            // Subtotal Diffs
            $sheet->setCellValue('I' . $row, $groupOut - $groupTAct);
            $sheet->setCellValue('J' . $row, $groupTAct > 0 ? ($groupOut / $groupTAct) : 0);
            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('0.00%');
            $sheet->setCellValue('K' . $row, $groupOut - $groupTPln);
            $sheet->setCellValue('L' . $row, $groupTPln > 0 ? ($groupOut / $groupTPln) : 0);
            $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('0.00%');

            $range = 'A' . $row . ':M' . $row;
            $sheet->getStyle($range)->getFont()->setBold(true);
            $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
            $row++;

            // Accumulate Grand Totals
            $isNS = str_contains(strtoupper($catName), 'NS');
            if ($isNS) {
                $nsGrand['mpPln'] += $groupMPPln; $nsGrand['tgtPln'] += $groupTPln;
                $nsGrand['mpAct'] += $groupMPAct; $nsGrand['tgtAct'] += $groupTAct;
                $nsGrand['out'] += $groupOut;
            } else {
                $dayGrand['mpPln'] += $groupMPPln; $dayGrand['tgtPln'] += $groupTPln;
                $dayGrand['mpAct'] += $groupMPAct; $dayGrand['tgtAct'] += $groupTAct;
                $dayGrand['out'] += $groupOut;
            }
        }

        // --- GRAND TOTALS ---
        $row++;
        $grandTotals = [
            ['label' => 'GRAND TOTAL DAY SHIFT (A)', 'data' => $dayGrand, 'color' => 'BDD7EE'],
            ['label' => 'GRAND TOTAL NIGHT SHIFT (NS)', 'data' => $nsGrand, 'color' => 'F2DCDB']
        ];

        foreach ($grandTotals as $gt) {
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue("A{$row}", $gt['label']);
            $sheet->setCellValue('C' . $row, $gt['data']['mpPln']);
            $sheet->setCellValue('D' . $row, $gt['data']['tgtPln']);
            $sheet->setCellValue('E' . $row, $gt['data']['mpAct']);
            $sheet->setCellValue('F' . $row, $gt['data']['tgtAct']);
            $sheet->setCellValue('G' . $row, $gt['data']['out']);
            
            // Diffs
            $sheet->setCellValue('I' . $row, $gt['data']['out'] - $gt['data']['tgtAct']);
            $sheet->setCellValue('J' . $row, $gt['data']['tgtAct'] > 0 ? ($gt['data']['out'] / $gt['data']['tgtAct']) : 0);
            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('0.00%');
            $sheet->setCellValue('K' . $row, $gt['data']['out'] - $gt['data']['tgtPln']);
            $sheet->setCellValue('L' . $row, $gt['data']['tgtPln'] > 0 ? ($gt['data']['out'] / $gt['data']['tgtPln']) : 0);
            $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('0.00%');

            $range = 'A' . $row . ':M' . $row;
            $sheet->getStyle($range)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($gt['color']);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $finalName = $fileName . '.xlsx';
        $tempPath = storage_path('app/public/' . $finalName);
        $writer->save($tempPath);

        return $tempPath;
    }

    private function groupLots($lots)
    {
        $groups = [];
        foreach ($lots as $l) {
            $configKey = $l->pivot->merge_id ?: "{$l->pivot->smv}-{$l->pivot->manpower}-{$l->pivot->working_hour}-{$l->pivot->section}";
            if (!isset($groups[$configKey])) {
                $groups[$configKey] = [];
            }
            $groups[$configKey][] = $l;
        }
        return array_values($groups);
    }

    private function addDrawing($sheet, $col, $row, $filePath)
    {
        try {
            $fullPath = storage_path('app/public/' . $filePath);
            if (!file_exists($fullPath)) return;

            $drawing = new Drawing();
            $drawing->setPath($fullPath);
            $drawing->setCoordinates($col . $row);
            $drawing->setHeight(70);
            $drawing->setWorksheet($sheet);
            
            // Center the image in the merged cell
            $drawing->setOffsetX(20);
            $drawing->setOffsetY(10);
        } catch (\Exception $e) {
            // Log or ignore
        }
    }

    private function drawGrandTotalBlock($sheet, $startCol, $row, $totals, $date)
    {
        $c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCol) - 1);
        $c2 = $startCol;
        $c3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 2);
        $c4 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 3);
        $c5 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 4);
        $c6 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 5);
        $c7 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 6);

        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12, 'italic' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THICK]],
        ];

        $labelStyle = [
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        $dataStyle = [
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        // Header Date & Line Group
        $sheet->mergeCells("{$c3}{$row}:{$c7}{$row}");
        $sheet->setCellValue("{$c3}{$row}", $date ? date('j-M-y', strtotime($date)) : '-');
        $sheet->getStyle("{$c3}{$row}")->applyFromArray($headerStyle);

        $row++;
        $sheet->mergeCells("{$c3}{$row}:{$c7}{$row}");
        $sheet->setCellValue("{$c3}{$row}", "TOTAL ACCUMULATION (All Lines)");
        $sheet->getStyle("{$c3}{$row}")->applyFromArray($headerStyle);

        $row++;
        // Column Headers
        $sheet->setCellValue("{$c3}{$row}", "TOTAL / AVERAGE");
        $sheet->getStyle("{$c3}{$row}")->getFont()->setItalic(true)->setBold(true);
        $sheet->getStyle("{$c3}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells("{$c3}{$row}:{$c5}{$row}");

        $sheet->setCellValue("{$c7}{$row}", "GRAND TOTAL");
        $sheet->getStyle("{$c7}{$row}")->getFont()->setItalic(true)->setBold(true);
        $sheet->getStyle("{$c7}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;
        // Metrics rows
        $metrics = [
            ['label' => 'Sewer/Helper', 'val' => $totals['sewers'] + $totals['manpower']],
            ['label' => 'Total Hours', 'val' => $totals['hours']],
            ['label' => 'Total Daily Target', 'val' => $totals['target']],
            ['label' => 'Total Production Output', 'val' => $totals['output']],
        ];

        foreach ($metrics as $m) {
            $sheet->mergeCells("{$c1}{$row}:{$c2}{$row}");
            $sheet->setCellValue("{$c1}{$row}", $m['label']);
            $sheet->getStyle("{$c1}{$row}")->applyFromArray($labelStyle);

            $sheet->mergeCells("{$c3}{$row}:{$c5}{$row}");
            $sheet->setCellValue("{$c3}{$row}", $m['val']);
            $sheet->getStyle("{$c3}{$row}")->applyFromArray($dataStyle);
            $sheet->getStyle("{$c3}{$row}")->getNumberFormat()->setFormatCode('#,##0');

            $sheet->setCellValue("{$c7}{$row}", $m['val']);
            $sheet->getStyle("{$c7}{$row}")->applyFromArray($dataStyle);
            $sheet->getStyle("{$c7}{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        // % Achieved row
        $sheet->mergeCells("{$c1}{$row}:{$c2}{$row}");
        $sheet->setCellValue("{$c1}{$row}", "% of Achieved");
        $sheet->getStyle("{$c1}{$row}")->applyFromArray($labelStyle);

        $ach = $totals['target'] > 0 ? ($totals['output'] / $totals['target']) : 0;

        $sheet->mergeCells("{$c3}{$row}:{$c5}{$row}");
        $sheet->setCellValue("{$c3}{$row}", $ach);
        $sheet->getStyle("{$c3}{$row}")->applyFromArray($dataStyle);
        $sheet->getStyle("{$c3}{$row}")->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle("{$c3}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFE699');

        $sheet->setCellValue("{$c7}{$row}", $ach);
        $sheet->getStyle("{$c7}{$row}")->applyFromArray($dataStyle);
        $sheet->getStyle("{$c7}{$row}")->getNumberFormat()->setFormatCode('0.00%');
        $row++;

        // Footer Special blocks
        $row++;
        $sheet->setCellValue("{$c3}{$row}", "WAITING CHECK");
        $sheet->getStyle("{$c3}{$row}")->getFont()->setBold(true)->setItalic(true);
        $sheet->getStyle("{$c3}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        $sheet->setCellValue("{$c5}{$row}", $totals['lines_count'] * 5); // Dummy or calculated logic
        $sheet->getStyle("{$c5}{$row}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("{$c5}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("{$c7}{$row}:{$c7}" . ($row+1));
        $sheet->setCellValue("{$c7}{$row}", "OFFLINE\nSEWER :");
        $sheet->getStyle("{$c7}{$row}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("{$c7}{$row}")->applyFromArray($headerStyle);

        $row++;
        $sheet->mergeCells("{$c3}{$row}:{$c4}{$row}");
        $sheet->setCellValue("{$c3}{$row}", "SUM PRECENTAGE SOA = ");
        $sheet->getStyle("{$c3}{$row}")->getFont()->setBold(true)->setItalic(true);
        $sheet->getStyle("{$c3}{$row}")->getFont()->getColor()->setRGB('FF0000');
        $sheet->getStyle("{$c3}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');

        $sheet->setCellValue("{$c5}{$row}", $ach);
        $sheet->getStyle("{$c5}{$row}")->getFont()->setBold(true)->setItalic(true);
        $sheet->getStyle("{$c5}{$row}")->getFont()->getColor()->setRGB('FF0000');
        $sheet->getStyle("{$c5}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
        $sheet->getStyle("{$c5}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$c5}{$row}")->getNumberFormat()->setFormatCode('0.00%');
    }

    private function getLineCategory($lineName) {
        $name = strtoupper($lineName);
        $isNS = str_contains($name, 'NS');
        preg_match('/\d+/', $name, $matches);
        $num = isset($matches[0]) ? (int)$matches[0] : null;

        if ($num === null) return 'OTHER';

        if (!$isNS) {
            if ($num >= 1 && $num <= 8) return 'A1-8';
            if ($num >= 9 && $num <= 16) return 'A9-16';
        } else {
            if ($num >= 1 && $num <= 8) return 'A1-8 NS';
            if ($num >= 9 && $num <= 16) return 'A9-16 NS';
        }
        return 'OTHER';
    }
}
