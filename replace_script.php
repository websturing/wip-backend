<?php

$file = '/var/www/html/wip/api/app/Features/Productivity/Services/ProductivityExportService.php';
$content = file_get_contents($file);

$start = strpos($content, '    private function drawGrandTotalBlock');
$end = strrpos($content, '}'); // The very last bracket
// Wait, the very last bracket is the class bracket.
$endFunction = strrpos($content, '}', $end - 1); // The bracket before the class bracket.

$newFunction = <<<'NEWFUNC'
    private function drawGrandTotalBlock($sheet, $startCol, $row, $allTotals, $catTotals, $prefix)
    {
        $c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCol) - 1);
        $c2 = $startCol;
        $c3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 2);
        $c4 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 3);
        $c5 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 4);
        $c6 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 5);
        $c7 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1) + 6);

        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
        
        foreach ($catTotals as $cat) {
            $sheet->mergeCells("{$c1}{$row}:{$c6}{$row}");
            $sheet->setCellValue("{$c1}{$row}", "SEWING ({$prefix}{$cat['start']} - {$prefix}{$cat['end']})");
            $sheet->getStyle("{$c1}{$row}")->applyFromArray($headerStyle);
            $row++;
            
            $sheet->mergeCells("{$c3}{$row}:{$c4}{$row}");
            $shiftName = $cat['isNs'] ? 'NIGHT' : 'DAY';
            $shiftCode = $cat['isNs'] ? 'NS' : 'DS';
            $sheet->setCellValue("{$c3}{$row}", "{$shiftName} ({$cat['start']}-{$cat['end']})");
            $sheet->getStyle("{$c3}{$row}")->applyFromArray($headerStyle)->getFont()->setItalic(true);
            
            $sheet->setCellValue("{$c6}{$row}", "TOTAL");
            $sheet->getStyle("{$c6}{$row}")->applyFromArray($headerStyle)->getFont()->setItalic(true);
            
            $sheet->setCellValue("{$c7}{$row}", "SPV {$cat['start']} - {$cat['end']} {$shiftCode}");
            $sheet->getStyle("{$c7}{$row}")->applyFromArray($headerStyle);
            $sheet->getStyle("{$c7}{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFC000');
            $row++;
            
            $avgHours = $cat['lines_count'] > 0 ? $cat['working_hour_sum'] / $cat['lines_count'] : 0;
            $metrics = [
                ['label' => 'Sewer/Helper', 'val' => $cat['sewers'] + $cat['manpower'], 'format' => '#,##0.0'],
                ['label' => 'Total Hours', 'val' => $avgHours, 'format' => '#,##0.0'],
                ['label' => 'Total Daily Target', 'val' => $cat['target'], 'format' => '#,##0.0'],
                ['label' => 'Total Production Output', 'val' => $cat['output'], 'format' => '#,##0.0'],
            ];
            
            foreach ($metrics as $m) {
                $sheet->mergeCells("{$c1}{$row}:{$c2}{$row}");
                $sheet->setCellValue("{$c1}{$row}", $m['label']);
                $sheet->getStyle("{$c1}{$row}:{$c2}{$row}")->getFont()->setBold(true);
                $sheet->getStyle("{$c1}{$row}:{$c2}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                $sheet->mergeCells("{$c3}{$row}:{$c4}{$row}");
                $sheet->setCellValue("{$c3}{$row}", $m['val']);
                $sheet->getStyle("{$c3}{$row}:{$c4}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$c3}{$row}:{$c4}{$row}")->getNumberFormat()->setFormatCode($m['format']);
                $sheet->getStyle("{$c3}{$row}:{$c4}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                $sheet->setCellValue("{$c6}{$row}", $m['val']);
                $sheet->getStyle("{$c6}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$c6}{$row}")->getNumberFormat()->setFormatCode($m['format']);
                $sheet->getStyle("{$c6}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle("{$c6}{$row}")->getFont()->setBold(true);
                $row++;
            }
            
            $ach = $cat['target'] > 0 ? ($cat['output'] / $cat['target']) : 0;
            $sheet->mergeCells("{$c1}{$row}:{$c2}{$row}");
            $sheet->setCellValue("{$c1}{$row}", "% of Achieved");
            
            $sheet->mergeCells("{$c3}{$row}:{$c4}{$row}");
            $sheet->setCellValue("{$c3}{$row}", $ach);
            
            $sheet->setCellValue("{$c6}{$row}", $ach);
            
            $sheet->setCellValue("{$c7}{$row}", "");
            
            $range = "{$c1}{$row}:{$c7}{$row}";
            $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getStyle($range)->getFont()->setBold(true);
            $sheet->getStyle("{$c3}{$row}")->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle("{$c6}{$row}")->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle("{$c1}{$row}:{$c4}{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFE699');
            $sheet->getStyle("{$c6}{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFE699');
            $row++;
        }
        
        $sheet->mergeCells("{$c1}{$row}:{$c6}{$row}");
        $sheet->setCellValue("{$c1}{$row}", "SEWING ALL SHIFT");
        $sheet->getStyle("{$c1}{$row}")->applyFromArray($headerStyle);
        $row++;
        
        $sheet->mergeCells("{$c3}{$row}:{$c4}{$row}");
        $sheet->setCellValue("{$c3}{$row}", "DAY - NIGHT");
        $sheet->getStyle("{$c3}{$row}")->applyFromArray($headerStyle)->getFont()->setItalic(true);
        
        $sheet->setCellValue("{$c6}{$row}", "TOTAL");
        $sheet->getStyle("{$c6}{$row}")->applyFromArray($headerStyle)->getFont()->setItalic(true);
        $row++;
        
        $avgHoursAll = $allTotals['lines_count'] > 0 ? $allTotals['working_hour_sum'] / $allTotals['lines_count'] : 0;
        $metricsAll = [
            ['label' => 'Sewer/Helper', 'val' => $allTotals['sewers'] + $allTotals['manpower'], 'format' => '#,##0.0'],
            ['label' => 'Total Hours', 'val' => $avgHoursAll, 'format' => '#,##0.0'],
            ['label' => 'Total Daily Target', 'val' => $allTotals['target'], 'format' => '#,##0.0'],
            ['label' => 'Total Production Output', 'val' => $allTotals['output'], 'format' => '#,##0.0'],
        ];
        
        foreach ($metricsAll as $m) {
            $sheet->mergeCells("{$c1}{$row}:{$c2}{$row}");
            $sheet->setCellValue("{$c1}{$row}", $m['label']);
            $sheet->getStyle("{$c1}{$row}:{$c2}{$row}")->getFont()->setBold(true);
            $sheet->getStyle("{$c1}{$row}:{$c2}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            $sheet->mergeCells("{$c3}{$row}:{$c4}{$row}");
            $sheet->setCellValue("{$c3}{$row}", $m['val']);
            $sheet->getStyle("{$c3}{$row}:{$c4}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$c3}{$row}:{$c4}{$row}")->getNumberFormat()->setFormatCode($m['format']);
            $sheet->getStyle("{$c3}{$row}:{$c4}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            $sheet->setCellValue("{$c6}{$row}", $m['val']);
            $sheet->getStyle("{$c6}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$c6}{$row}")->getNumberFormat()->setFormatCode($m['format']);
            $sheet->getStyle("{$c6}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getStyle("{$c6}{$row}")->getFont()->setBold(true);
            $row++;
        }
        
        $achAll = $allTotals['target'] > 0 ? ($allTotals['output'] / $allTotals['target']) : 0;
        $sheet->mergeCells("{$c1}{$row}:{$c2}{$row}");
        $sheet->setCellValue("{$c1}{$row}", "% of Achieved");
        
        $sheet->mergeCells("{$c3}{$row}:{$c4}{$row}");
        $sheet->setCellValue("{$c3}{$row}", $achAll);
        
        $sheet->setCellValue("{$c6}{$row}", $achAll);
        
        $range = "{$c1}{$row}:{$c6}{$row}";
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle("{$c3}{$row}")->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle("{$c6}{$row}")->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle("{$c1}{$row}:{$c4}{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFE699');
        $sheet->getStyle("{$c6}{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFE699');
        $row += 2;
        
        $sheet->mergeCells("{$c2}{$row}:{$c3}{$row}");
        $sheet->setCellValue("{$c2}{$row}", "Production Manager");
        $sheet->mergeCells("{$c4}{$row}:{$c5}{$row}");
        $sheet->setCellValue("{$c4}{$row}", "Production Manager");
        $sheet->mergeCells("{$c7}{$row}:{$c7}{$row}");
        $sheet->setCellValue("{$c7}{$row}", "FACTORY MANAGER");
        $sheet->getStyle("{$c2}{$row}:{$c7}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("{$c2}{$row}:{$c7}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$c2}{$row}:{$c3}{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFC000');
        $sheet->getStyle("{$c4}{$row}:{$c5}{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFC000');
        $sheet->getStyle("{$c7}{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFC000');
        $sheet->getStyle("{$c2}{$row}:{$c3}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("{$c4}{$row}:{$c5}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("{$c7}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        $sheet->mergeCells("{$c2}" . ($row+1) . ":{$c3}" . ($row+2));
        $sheet->mergeCells("{$c4}" . ($row+1) . ":{$c5}" . ($row+2));
        $sheet->mergeCells("{$c7}" . ($row+1) . ":{$c7}" . ($row+2));
        $sheet->getStyle("{$c2}" . ($row+1) . ":{$c3}" . ($row+2))->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("{$c4}" . ($row+1) . ":{$c5}" . ($row+2))->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("{$c7}" . ($row+1) . ":{$c7}" . ($row+2))->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $row += 3;
        
        $sheet->mergeCells("{$c2}{$row}:{$c3}{$row}");
        $sheet->setCellValue("{$c2}{$row}", "MS. Sri Sutarmi");
        $sheet->mergeCells("{$c4}{$row}:{$c5}{$row}");
        $sheet->setCellValue("{$c4}{$row}", "MS. Anabel");
        $sheet->mergeCells("{$c7}{$row}:{$c7}{$row}");
        $sheet->setCellValue("{$c7}{$row}", "MS. QING FEN YE");
        $sheet->getStyle("{$c2}{$row}:{$c7}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("{$c2}{$row}:{$c7}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$c2}{$row}:{$c3}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("{$c4}{$row}:{$c5}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("{$c7}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $row++;
        
        $sheet->mergeCells("{$c2}{$row}:{$c3}{$row}");
        $sheet->setCellValue("{$c2}{$row}", "WAITING CHECK");
        $sheet->getStyle("{$c2}{$row}")->getFont()->setBold(true)->setItalic(true);
        $sheet->getStyle("{$c2}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$c2}{$row}:{$c3}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->mergeCells("{$c4}{$row}:{$c5}{$row}");
        $sheet->setCellValue("{$c4}{$row}", $allTotals['lines_count'] * 5);
        $sheet->getStyle("{$c4}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("{$c4}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$c4}{$row}:{$c5}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->setCellValue("{$c6}{$row}", "OFFLINE\nSEWER :");
        $sheet->getStyle("{$c6}{$row}")->getFont()->setBold(true)->setItalic(true);
        $sheet->getStyle("{$c6}{$row}")->getAlignment()->setWrapText(true)->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$c6}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->setCellValue("{$c7}{$row}", "10");
        $sheet->getStyle("{$c7}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("{$c7}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$c7}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $row++;
        
        $sheet->mergeCells("{$c2}{$row}:{$c5}{$row}");
        $sheet->getStyle("{$c2}{$row}:{$c5}{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
        $sheet->getStyle("{$c2}{$row}:{$c5}{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
NEWFUNC;

$regex = '/    private function drawGrandTotalBlock\([^\n]+\n.*\}[\s]*\}\s*$/s';

$content = substr($content, 0, $start) . $newFunction . "\n    }\n}\n";
file_put_contents($file, $content);

echo "Done replacing drawGrandTotalBlock.\n";
