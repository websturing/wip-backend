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
        
        if ($type === 'sewing_output') {
            return $this->generateSewingOutputFile($productivities, 'Sewing_Output_' . $date, $date);
        }

        return $this->generateFile($productivities, 'Daily_Productivity_' . $date);
    }

    private function generateFile($productivities, $fileName)
    {
        // 1. Pre-fetch all necessary data to avoid N+1 in export
        $allLineIds = $productivities->pluck('line_id')->unique();
        $dates = $productivities->pluck('date')->unique();

        $productionData = Production::whereIn('line_id', $allLineIds)
            ->whereIn('production_date', $dates)
            ->with(['items.details'])
            ->get();

        // 2. Fetch Cutting Data from external API
        $uniqueLotCodes = $productivities->flatMap->lots->pluck('lot_code')->unique();
        $cuttingCache = [];
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
                        $totalCut = (int)($target['grand_total']['cut_qty'] ?? 0);
                        if ($totalCut === 0 && isset($target['summary_by_color'])) {
                            foreach ($target['summary_by_color'] as $color) {
                                $totalCut += (int)($color['total_qty'] ?? $color['qty'] ?? $color['total_cut'] ?? 0);
                            }
                        }
                        $cuttingCache[$lotCode] = $totalCut;
                    }
                }
            }
        }

        // 3. Create Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productivity');

        $row = 1;
        foreach ($productivities as $productivity) {
            $groupedLots = $this->groupLots($productivity->lots);

            foreach ($groupedLots as $lotGroup) {
                $this->drawStyleBlock($sheet, 'B', $row, $lotGroup, $productivity, $productionData, $cuttingCache);
                $row += 8;
            }
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
        $smv = (float)($firstLot->pivot->smv ?? 0);
        $mp = (int)($firstLot->pivot->manpower ?? 0);
        $mg = (int)($firstLot->pivot->sewer ?? 0);
        $wh = (float)($firstLot->pivot->working_hour ?? 8);
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

        // --- ROW 1 (Header: Line & Target Output) ---
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

        $sheet->mergeCells("{$c5}" . ($row + 2) . ":{$c5}" . ($row + 2));
        $sheet->setCellValue("{$c5}" . ($row + 2), $combinedLots);
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
        $sheet->getStyle("{$c7}{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $sheet->setCellValue("{$c6}" . ($row + 1), "Daily Target");
        $sheet->getStyle("{$c6}" . ($row + 1))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $target = $smv > 0 ? floor(($mp + $mg) * 8 * 60 / $smv) : 0;
        $sheet->setCellValue("{$c7}" . ($row + 1), number_format($target, 0, ',', '.'));
        $sheet->getStyle("{$c7}" . ($row + 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->setCellValue("{$c6}" . ($row + 2), "Prod Output");
        $sheet->getStyle("{$c6}" . ($row + 2))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $lpItems = $productionData->where('line_id', $productivity->line_id)->flatMap->items;
        $output = $lpItems->filter(fn($i) => in_array($i->lot_id, $lotIds))->flatMap->details->sum('qty_output');

        $sheet->setCellValue("{$c7}" . ($row + 2), number_format($output, 0, ',', '.'));
        $sheet->getStyle("{$c7}" . ($row + 2))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->setCellValue("{$c6}" . ($row + 3), "% Achieved");
        $sheet->getStyle("{$c6}" . ($row + 3))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 3))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $achieved = $target > 0 ? ($output / $target) : 0;
        $cellAch = $c7 . ($row + 3);
        $sheet->setCellValue($cellAch, number_format($achieved * 100, 2, ',', '.') . "%");
        $sheet->getStyle($cellAch)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
        $sheet->getStyle($cellAch)->getFont()->setBold(true);
        $sheet->getStyle($cellAch)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->setCellValue("{$c6}" . ($row + 4), "Bal. Qty");
        $sheet->getStyle("{$c6}" . ($row + 4))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->setCellValue("{$c7}" . ($row + 4), number_format($target - $output, 0, ',', '.'));
        $sheet->getStyle("{$c7}" . ($row + 4))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // EXTRA DECORATION: Orange bottom for Section Label
        $sheet->mergeCells("{$c5}" . ($row+6) . ":{$c5}" . ($row+6));
        $section = strtoupper($firstLot->pivot->section ?? 'ALL');
        $sheet->setCellValue("{$c5}" . ($row+6), $section);
        $sheet->getStyle("{$c5}" . ($row+6))->applyFromArray($headerStyle);
        $sheet->getStyle("{$c5}" . ($row+6))->getFill()->getStartColor()->setRGB('FFC000');

        // Row height adjustment
        for ($i = 0; $i <= 6; $i++) {
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
            'Line', 'LOT', 'Sect', 
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
        
        // Fetch production data for output calculations
        $allLineIds = $productivities->pluck('line_id')->unique();
        $productionData = Production::whereIn('line_id', $allLineIds)
            ->whereDate('production_date', $date)
            ->with(['items.details'])
            ->get();

        foreach ($productivities as $p) {
            $groupedLots = $this->groupLots($p->lots);

            foreach ($groupedLots as $lotGroup) {
                $firstLot = $lotGroup[0];
                
                // Aggregation for Group
                $combinedLots = collect($lotGroup)->map(fn($l) => ltrim($l->lot_code, '0'))->join(' + ');
                $combinedStyles = collect($lotGroup)->map(fn($l) => $l->style_no)->unique()->join(' / ');
                $combinedBuyers = collect($lotGroup)->map(fn($l) => $l->glGroup->customer->name ?? '-')->unique()->join(' / ');
                $combinedGLInfo = collect($lotGroup)->map(fn($l) => $l->glGroup->gl_number)->unique()->join(' / ') . ' / ' . $combinedLots;
                
                $lotIds = collect($lotGroup)->pluck('id')->toArray();
                $smv = (float)($firstLot->pivot->smv ?? 0);
                $mpAct = ($firstLot->pivot->manpower ?? 0) + ($firstLot->pivot->sewer ?? 0);
                $mpPln = $firstLot->pivot->plan_manpower ?? 0;
                $tgtPln = collect($lotGroup)->sum(fn($l) => $l->pivot->target_plan ?? 0);
                $wh = $firstLot->pivot->working_hour ?? 8;
                $tgtAct = $smv > 0 ? floor(($mpAct * $wh * 60) / $smv) : 0;

                $lpItems = $productionData->where('line_id', $p->line_id)->flatMap->items;
                $lotItems = $lpItems->filter(fn($pi) => in_array($pi->lot_id, $lotIds));
                $output = $lotItems->flatMap->details->sum('qty_output');
                $colors = $lotItems->pluck('color')->unique()->join(', ');
                $lastStep = collect($lotGroup)->max(fn($l) => $l->pivot->last_step ?? 0);

                $sheet->setCellValue('A' . $row, $p->line->name);
                $sheet->setCellValue('B' . $row, $combinedLots);
                $sheet->setCellValue('C' . $row, strtoupper($firstLot->pivot->section ?? 'ALL'));
                $sheet->setCellValue('D' . $row, $mpPln);
                $sheet->setCellValue('E' . $row, $tgtPln);
                $sheet->setCellValue('F' . $row, $mpAct);
                $sheet->setCellValue('G' . $row, $tgtAct);
                $sheet->setCellValue('H' . $row, $output);
                $sheet->setCellValue('I' . $row, $lastStep);
                
                // Diff & % vs Target Act
                $sheet->setCellValue('J' . $row, $output - $tgtAct);
                $sheet->setCellValue('K' . $row, $tgtAct > 0 ? round(($output / $tgtAct) * 100, 2) . '%' : '0%');
                
                // Diff & % vs Target Plan
                $sheet->setCellValue('L' . $row, $output - $tgtPln);
                $sheet->setCellValue('M' . $row, $tgtPln > 0 ? round(($output / $tgtPln) * 100, 2) . '%' : '0%');
                
                $sheet->setCellValue('N' . $row, ''); // Remarks

                // Zebra stripes & Borders
                $range = 'A' . $row . ':N' . $row;
                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                if ($row % 2 === 0) {
                    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
                }

                $row++;
            }
        }

        // Auto-size columns
        foreach (range('A', 'N') as $col) {
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
            $configKey = "{$l->pivot->smv}-{$l->pivot->manpower}-{$l->pivot->working_hour}-{$l->pivot->section}";
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
}
