<?php

namespace App\Features\Reference\Services;

use App\Features\Reference\Models\Customer;
use App\Features\Reference\Models\GlGroup;
use App\Features\Reference\Models\Lot;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;

class ImportService
{
    protected $summary = [
        'total' => 0,
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => []
    ];

    protected $customerCache = [];
    protected $glCache = [];

    public function import($filePath)
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (empty($rows)) {
            throw new \Exception("The Excel file is empty.");
        }

        // 1. Get header row to find column mappings
        $header = array_shift($rows);
        
        // Find column indices by header name (case-insensitive)
        $indices = [
            'customer' => $this->findColumn($header, ['customer', 'cust']),
            'gl' => $this->findColumn($header, ['gl', 'gl group', 'gl number']),
            'lot' => $this->findColumn($header, ['lot', 'lot number']),
            'gmt_qty' => $this->findColumn($header, ['gmtqty', 'gmt_qty', 'gmt qty', 'quantity', 'qty']),
            'style_no' => $this->findColumn($header, ['styleno', 'style_no', 'style no', 'style']),
            'brand' => $this->findColumn($header, ['brand']),
            'sam' => $this->findColumn($header, ['sam']),
            'delivery_date' => $this->findColumn($header, ['deldate', 'delivery', 'delivery date', 'ship date']),
            'order_date' => $this->findColumn($header, ['orderdate', 'order date']),
        ];

        // Fill in defaults if not found (based on previous mapping)
        $indices['customer'] = $indices['customer'] ?? 5; // Column F
        $indices['gl'] = $indices['gl'] ?? 4; // Column E
        $indices['lot'] = $indices['lot'] ?? 16; // Column Q
        $indices['gmt_qty'] = $indices['gmt_qty'] ?? 17;

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                // If the row is empty, skip it
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = [
                    'customer_name' => trim($row[$indices['customer']] ?? ''),
                    'customer_country' => null, 
                    'gl_number' => trim($row[$indices['gl']] ?? ''),
                    'lot_number' => trim($row[$indices['lot']] ?? ''),
                    'gmt_qty' => trim($row[$indices['gmt_qty']] ?? null),
                    'is_cancelled' => false,
                    'style_no' => trim($row[$indices['style_no']] ?? null),
                    'brand' => trim($row[$indices['brand']] ?? null),
                    'sam' => trim($row[$indices['sam']] ?? null),
                    'delivery_date' => trim($row[$indices['delivery_date']] ?? null),
                    'order_date' => trim($row[$indices['order_date']] ?? null),
                ];

                $this->summary['total']++;

                // 2. Validation
                if (empty($data['customer_name']) || empty($data['gl_number']) || empty($data['lot_number'])) {
                    $this->summary['skipped']++;
                    $this->summary['errors'][] = "Row " . ($index + 2) . ": Missing required fields (Customer, GL, or Lot).";
                    continue;
                }

                // 3. Normalization
                $data['gl_number'] = strtoupper(substr($data['gl_number'], -5));
                $data['lot_number'] = str_pad($data['lot_number'], 2, '0', STR_PAD_LEFT);
                $data['gmt_qty'] = filter_var($data['gmt_qty'], FILTER_VALIDATE_INT) === false ? null : (int)$data['gmt_qty'];
                // Date normalization (handle Excel date numbers or strings)
                try {
                    if (!empty($data['delivery_date'])) {
                        if (is_numeric($data['delivery_date'])) {
                            $data['delivery_date'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data['delivery_date'])->format('Y-m-d');
                        } else {
                            // Try to parse string date (e.g. 1/8/2026)
                            $data['delivery_date'] = \Carbon\Carbon::parse(str_replace(['\\', '/'], '-', $data['delivery_date']))->format('Y-m-d');
                        }
                    } else {
                        $data['delivery_date'] = null;
                    }

                    if (!empty($data['order_date'])) {
                        if (is_numeric($data['order_date'])) {
                            $data['order_date'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data['order_date'])->format('Y-m-d');
                        } else {
                            $data['order_date'] = \Carbon\Carbon::parse(str_replace(['\\', '/'], '-', $data['order_date']))->format('Y-m-d');
                        }
                    } else {
                        $data['order_date'] = null;
                    }
                } catch (\Exception $e) {
                    // If date parsing fails, set to null instead of crashing
                    $data['delivery_date'] = null;
                    $data['order_date'] = null;
                }

                // 4. Processing
                $this->processRow($data);
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
                if (str_contains($cell, $term)) {
                    return $index;
                }
            }
        }
        return null;
    }

    protected function processRow($data)
    {
        // 1. Handle Customer (Upsert by Name)
        $customerName = $data['customer_name'];
        if (!isset($this->customerCache[$customerName])) {
            $customer = Customer::firstOrCreate(['name' => $customerName]);
            $this->customerCache[$customerName] = $customer->id;
        }
        $customerId = $this->customerCache[$customerName];

        // 2. Handle GL Group (Unique by Customer + GL Number)
        $glNumber = $data['gl_number'];
        $glKey = $customerId . '|' . $glNumber;
        if (!isset($this->glCache[$glKey])) {
            $glGroup = GlGroup::firstOrCreate([
                'customer_id' => $customerId,
                'gl_number' => $glNumber
            ]);
            $this->glCache[$glKey] = $glGroup->id;
        }
        $glId = $this->glCache[$glKey];

        // 3. Handle Lot (Upsert by GL + Lot Number)
        $lot = Lot::where('gl_id', $glId)->where('lot_number', $data['lot_number'])->first();

        $lotData = [
            'is_cancelled' => $data['is_cancelled'],
            'gmt_qty' => $data['gmt_qty'],
            'style_no' => $data['style_no'],
            'brand' => $data['brand'],
            'sam' => $data['sam'],
            'delivery_date' => $data['delivery_date'],
            'order_date' => $data['order_date'],
        ];

        if ($lot) {
            // Updated behavior (Idempotent)
            $lot->update($lotData);
            if ($lot->wasChanged()) {
                $this->summary['updated']++;
            }
        } else {
            Lot::create(array_merge($lotData, [
                'gl_id' => $glId,
                'lot_number' => $data['lot_number'],
            ]));
            $this->summary['inserted']++;
        }
    }
}
