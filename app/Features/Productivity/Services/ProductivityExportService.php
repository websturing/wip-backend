<?php

namespace App\Features\Productivity\Services;

use App\Features\Productivity\Models\Productivity;
use App\Features\Production\Models\ProductionItemDetail;
use App\Features\Reference\Models\Lot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
        
        // Load pivot media manually for each lot if not loaded
        $productivity->lots->each(function($l) {
            $l->pivot->load('media');
        });

        // 1. Fetch Production Data for this Line and Date
        $productionData = \App\Features\Production\Models\Production::where('line_id', $productivity->line_id)
            ->whereDate('production_date', $productivity->date)
            ->with(['items.details'])
            ->get();

        // 2. Fetch Cutting Data from external API
        $uniqueLotCodes = $productivity->lots->pluck('lot_code')->unique();
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

        $col = 'A';
        $row = 1;

        foreach ($productivity->lots as $index => $lot) {
            $this->drawStyleBlock($sheet, $col, $row, $lot, $productivity, $productionData, $cuttingCache);
            // Move to next block (Assuming 6 columns per block)
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($col) + 6);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Productivity_' . $productivity->line->name . '_' . $productivity->date . '.xlsx';
        $tempPath = storage_path('app/public/' . $fileName);
        $writer->save($tempPath);

        return $tempPath;
    }

    private function drawStyleBlock($sheet, $startCol, $row, $lot, $productivity, $productionData, $cuttingCache)
    {
        $c1 = $startCol;
        $c2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 1);
        $c3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 2);
        $c4 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 3);
        $c5 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 4);
        $c6 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 5);

        // Styling Defaults
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEEEEE']]
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

        // --- ROW 1 (Header: Line & Buyer) ---
        $sheet->mergeCells("{$c1}{$row}:{$c1}" . ($row + 5)); // Line Name Vertical
        $sheet->setCellValue("{$c1}{$row}", $productivity->line->name);
        $sheet->getStyle("{$c1}{$row}")->applyFromArray($headerStyle);
        $sheet->getStyle("{$c1}{$row}")->getAlignment()->setTextRotation(90);

        $sheet->mergeCells("{$c2}{$row}:{$c2}" . ($row + 1));
        $sheet->setCellValue("{$c2}{$row}", "Sewer/Helper | 车工/外勤工");
        $sheet->getStyle("{$c2}{$row}")->applyFromArray($labelStyle);

        $sheet->mergeCells("{$c3}{$row}:{$c4}{$row}");
        $sheet->setCellValue("{$c3}{$row}", $lot->glGroup->customer->name ?? 'UNKNOWN');
        $sheet->getStyle("{$c3}{$row}")->applyFromArray($headerStyle);
        
        // --- ROW 2 (MP & MG) ---
        $mp = (int)($lot->pivot->manpower ?? $productivity->manpower);
        $mg = (int)($lot->pivot->sewer ?? $productivity->sewer);
        $sheet->setCellValue("{$c3}" . ($row + 1), $mp);
        $sheet->setCellValue("{$c4}" . ($row + 1), $mg);
        $sheet->getStyle("{$c3}" . ($row + 1))->applyFromArray($dataStyle);
        $sheet->getStyle("{$c4}" . ($row + 1))->applyFromArray($dataStyle);

        // --- ROW 3 (Working Hour) ---
        $wh = (float)($lot->pivot->working_hour ?? $productivity->working_hour);
        $sheet->mergeCells("{$c2}" . ($row + 2) . ":{$c2}" . ($row + 2));
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
        $smv = (float)($lot->pivot->smv ?? $productivity->smv);
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
        $sheet->setCellValue("{$c5}{$row}", $lot->glGroup->gl_number);
        $sheet->getStyle("{$c5}{$row}")->applyFromArray($headerStyle);

        $sheet->mergeCells("{$c5}" . ($row + 2) . ":{$c5}" . ($row + 2));
        $sheet->setCellValue("{$c5}" . ($row + 2), $lot->lot_code);
        $sheet->getStyle("{$c5}" . ($row + 2))->applyFromArray($labelStyle);

        // Image Placeholder
        $sheet->mergeCells("{$c5}" . ($row + 3) . ":{$c5}" . ($row + 5));
        if ($lot->pivot->media && $lot->pivot->media->file_path) {
            $this->addDrawing($sheet, $c5, $row + 3, $lot->pivot->media->file_path);
        }

        // --- RIGHT COLUMN (Metrics) ---
        $sheet->setCellValue("{$c6}{$row}", "Order Qty");
        $sheet->getStyle("{$c6}{$row}")->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        // Order Qty from Cutting
        $orderQty = $cuttingCache[$lot->lot_code] ?? 0;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c6) + 1) . $row, $orderQty);
        
        $sheet->setCellValue("{$c6}" . ($row + 1), "Daily Target");
        $sheet->getStyle("{$c6}" . ($row + 1))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $target = $smv > 0 ? floor(($mp + $mg) * 8 * 60 / $smv) : 0;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c6) + 1) . ($row + 1), number_format($target, 0, ',', '.'));

        $sheet->setCellValue("{$c6}" . ($row + 2), "Prod Output");
        $sheet->getStyle("{$c6}" . ($row + 2))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        // Output from production tables
        $output = $productionData->flatMap->items->filter(fn($i) => $i->lot_id == $lot->id)->flatMap->details->sum('qty_output');
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c6) + 1) . ($row + 2), number_format($output, 0, ',', '.'));

        $sheet->setCellValue("{$c6}" . ($row + 3), "% Achieved");
        $sheet->getStyle("{$c6}" . ($row + 3))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 3))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $achieved = $target > 0 ? ($output / $target) : 0;
        $cellAch = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c6) + 1) . ($row + 3);
        $sheet->setCellValue($cellAch, number_format($achieved * 100, 2, ',', '.') . "%");
        $sheet->getStyle($cellAch)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');

        $sheet->setCellValue("{$c6}" . ($row + 4), "Bal. Qty");
        $sheet->getStyle("{$c6}" . ($row + 4))->applyFromArray($labelStyle);
        $sheet->getStyle("{$c6}" . ($row + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c6) + 1) . ($row + 4), number_format($target - $output, 0, ',', '.'));

        // Row height adjustment
        for ($i = 0; $i <= 5; $i++) {
            $sheet->getRowDimension($row + $i)->setRowHeight(25);
        }
    }

    private function addDrawing($sheet, $col, $row, $filePath)
    {
        try {
            $fullPath = storage_path('app/' . $filePath);
            if (!file_exists($fullPath)) return;

            $drawing = new Drawing();
            $drawing->setPath($fullPath);
            $drawing->setCoordinates($col . $row);
            $drawing->setHeight(80);
            $drawing->setWorksheet($sheet);
            
            // Center the image in the merged cell
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);
        } catch (\Exception $e) {
            // Log or ignore
        }
    }
}
