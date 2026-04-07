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

        // 1. Skip header row
        array_shift($rows);

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                // Mapping based on User's Image:
                // Index 5: Customer (Column F)
                // Index 4: GL (Column E)
                // Index 16: Lot (Column Q)
                
                $data = [
                    'customer_name' => trim($row[5] ?? ''),
                    'customer_country' => null, // Not visible in image
                    'gl_number' => trim($row[4] ?? ''),
                    'lot_number' => trim($row[16] ?? ''),
                    'is_cancelled' => false, // Default to false
                ];

                $this->summary['total']++;

                // 2. Validation
                if (empty($data['customer_name']) || empty($data['gl_number']) || empty($data['lot_number'])) {
                    $this->summary['skipped']++;
                    $this->summary['errors'][] = "Row " . ($index + 2) . ": Missing required fields (Customer, GL, or Lot).";
                    continue;
                }

                // 3. Normalization
                $data['gl_number'] = strtoupper($data['gl_number']);
                $data['lot_number'] = str_pad($data['lot_number'], 2, '0', STR_PAD_LEFT);

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

        if ($lot) {
            // Updated behavior (Idempotent)
            $lot->update(['is_cancelled' => $data['is_cancelled']]);
            if ($lot->wasChanged()) {
                $this->summary['updated']++;
            }
        } else {
            Lot::create([
                'gl_id' => $glId,
                'lot_number' => $data['lot_number'],
                'is_cancelled' => $data['is_cancelled']
            ]);
            $this->summary['inserted']++;
        }
    }
}
