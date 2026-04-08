<?php

namespace App\Features\Production\Services;

use App\Features\Production\Models\Production;
use App\Features\Production\Models\ProductionItem;
use App\Features\Production\Models\ProductionItemDetail;
use App\Features\Reference\Models\GlGroup;
use App\Features\Reference\Models\Lot;
use App\Features\Lines\Models\Line;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProductionImportService
{
    protected $summary = [
        'total' => 0,
        'inserted' => 0,
        'errors' => []
    ];

    protected $lineCache = [];
    protected $glCache = [];
    protected $apiColorCache = [];

    public function import($filePath)
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (empty($rows)) {
            throw new \Exception("The Excel file is empty.");
        }

        $header = array_shift($rows);
        
        // Dynamic Mapping
        $indices = [
            'gl' => $this->findColumn($header, ['gl', 'gl #', 'gl number']),
            'date' => $this->findColumn($header, ['date', 'production date']),
            'qty' => $this->findColumn($header, ['qty', 'quantity', 'pcs']),
            'line' => $this->findColumn($header, ['line']),
            'color' => $this->findColumn($header, ['color', 'colour']),
        ];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) continue;

                $this->summary['total']++;

                $rawGl = trim($row[$indices['gl']] ?? '');
                $rawDate = trim($row[$indices['date']] ?? '');
                $rawQty = trim($row[$indices['qty']] ?? 0);
                $rawLine = trim($row[$indices['line']] ?? '');
                $rawColor = trim($row[$indices['color']] ?? '');

                // 1. Parse Date
                $date = $this->normalizeDate($rawDate);
                if (!$date) {
                    $this->summary['errors'][] = "Row " . ($index + 2) . ": Invalid date ($rawDate)";
                    continue;
                }

                // 2. Find Line
                $lineId = $this->getLineId($rawLine);
                if (!$lineId) {
                    $this->summary['errors'][] = "Row " . ($index + 2) . ": Line not found ($rawLine)";
                    continue;
                }

                // 3. Parse GL and Lot from "GL #" (e.g. 65969-00P)
                // We assume format is GL-LOT suffix
                $glNo = $rawGl;
                $lotNo = '00'; // Default
                if (str_contains($rawGl, '-')) {
                    $parts = explode('-', $rawGl);
                    $glNo = $parts[0];
                    // Extract numeric part from 00P -> 00
                    preg_match('/\d+/', $parts[1] ?? '00', $matches);
                    $lotNo = str_pad($matches[0] ?? '00', 2, '0', STR_PAD_LEFT);
                }
                
                // Clean GL No (take last 5 as per user's previous request)
                $glNo = strtoupper(substr($glNo, -5));

                $lot = $this->findLot($glNo, $lotNo);
                if (!$lot) {
                    $this->summary['errors'][] = "Row " . ($index + 2) . ": GL/Lot reference not found ($glNo-$lotNo)";
                    continue;
                }

                // 4. Fuzzy Match Color
                $matchedColor = $this->fuzzyMatchColor($glNo, $rawColor);
                if (!$matchedColor) {
                    $matchedColor = strtoupper($rawColor);
                }

                // 5. Create Production Record
                $production = Production::firstOrCreate([
                    'line_id' => $lineId,
                    'production_date' => $date
                ]);

                // 6. Create Production Item
                $productionItem = ProductionItem::firstOrCreate([
                    'production_id' => $production->id,
                    'lot_id' => $lot->id,
                    'color' => $matchedColor,
                ]);

                // 7. Create Production Item Detail (Bulk mode / TOTAL size)
                ProductionItemDetail::create([
                    'production_item_id' => $productionItem->id,
                    'size_name' => 'TOTAL',
                    'qty_input' => 0,
                    'qty_output' => (int)str_replace(['.', ','], '', (string)$rawQty)
                ]);

                $this->summary['inserted']++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $this->summary;
    }

    protected function findColumn($header, $searchTerms)
    {
        foreach ($header as $index => $cell) {
            $cell = strtolower(trim((string)$cell));
            foreach ($searchTerms as $term) {
                if (str_contains($cell, $term)) return $index;
            }
        }
        return null;
    }

    protected function normalizeDate($val)
    {
        if (empty($val)) return null;
        try {
            if (is_numeric($val)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d');
            }
            // For formats like "26-Feb", we need to append current year if missing
            $date = Carbon::parse(str_replace(['\\', '/'], '-', $val));
            if (!$date->year || $date->year < 2000) $date->year = date('Y');
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getLineId($name)
    {
        if (isset($this->lineCache[$name])) return $this->lineCache[$name];
        $line = Line::where('name', $name)->first();
        if ($line) {
            $this->lineCache[$name] = $line->id;
            return $line->id;
        }
        return null;
    }

    protected function findLot($glNo, $lotNo)
    {
        $glKey = $glNo . '|' . $lotNo;
        if (isset($this->glCache[$glKey])) return $this->glCache[$glKey];

        $glGroup = GlGroup::where('gl_number', 'LIKE', "%$glNo")->first();
        if (!$glGroup) return null;

        $lot = Lot::where('gl_id', $glGroup->id)->where('lot_number', $lotNo)->first();
        if ($lot) {
            $this->glCache[$glKey] = $lot;
            return $lot;
        }
        return null;
    }

    protected function fuzzyMatchColor($glNo, $excelColor)
    {
        $excelColor = strtoupper(trim($excelColor));
        if (empty($excelColor)) return null;

        // Get colors from API for this GL
        if (!isset($this->apiColorCache[$glNo])) {
            try {
                $response = Http::get("https://cutting.glaindonesia.lan/api/gl-number/summary-by-gl/$glNo");
                if ($response->successful()) {
                    $data = $response->json();
                    $this->apiColorCache[$glNo] = array_map(function($c) { return strtoupper($c['color']); }, $data['summary_by_color'] ?? []);
                } else {
                    $this->apiColorCache[$glNo] = [];
                }
            } catch (\Exception $e) {
                $this->apiColorCache[$glNo] = [];
            }
        }

        $apiColors = $this->apiColorCache[$glNo];
        if (empty($apiColors)) return $excelColor;

        // 1. Exact match
        if (in_array($excelColor, $apiColors)) return $excelColor;

        // 2. Fuzzy match: Word-based similarity
        // Break excel color into words
        $excelWords = explode(' ', $excelColor);
        
        foreach ($apiColors as $apiColor) {
            // If api color contains all words of excel color or vice-versa
            if (str_contains($apiColor, $excelColor) || str_contains($excelColor, $apiColor)) {
                return $apiColor;
            }
            
            // Check individual words
            foreach ($excelWords as $word) {
                if (strlen($word) > 2 && str_contains($apiColor, $word)) {
                    return $apiColor;
                }
            }
        }

        return $excelColor;
    }
}
