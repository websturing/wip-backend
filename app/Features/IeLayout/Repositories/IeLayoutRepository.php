<?php

namespace App\Features\IeLayout\Repositories;

use App\Features\IeLayout\Models\IeLayout;
use App\Features\IeLayout\Models\TimeStudy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IeLayoutRepository
{
    /**
     * Get all IE layouts with their details and related models, with optional filtering.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = IeLayout::with(['details.operation', 'createdBy', 'updatedBy', 'lot']);

        if (isset($filters['lot_id'])) {
            $query->where('lot_id', $filters['lot_id']);
        }

        return $query->get();
    }

    /**
     * Find an IE layout by ID with details.
     */
    public function findById(int $id): ?IeLayout
    {
        return IeLayout::with(['details.operation', 'createdBy', 'updatedBy', 'lot'])->find($id);
    }

    /**
     * Create a new IE layout and its details.
     */
    public function create(array $data): IeLayout
    {
        return DB::transaction(function () use ($data) {
            $ieLayoutData = collect($data)->except('details')->toArray();
            $ieLayout = IeLayout::create($ieLayoutData);

            if (isset($data['details'])) {
                foreach ($data['details'] as $detail) {
                    $detail = $this->calculateMetrics($detail, $ieLayout->efficiency_constant);
                    $detail['ie_layout_id'] = $ieLayout->id;
                    $detail['created_by_id'] = $ieLayout->created_by_id;
                    $detail['updated_by_id'] = $ieLayout->updated_by_id;
                    
                    // Clean up for DB
                    $dbData = collect($detail)->only((new TimeStudy())->getFillable())->toArray();
                    TimeStudy::create($dbData);
                }
            }

            return $ieLayout->load(['details.operation']);
        });
    }

    /**
     * Update an IE layout and sync its details.
     */
    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $ieLayout = IeLayout::findOrFail($id);
            $ieLayoutData = collect($data)->except('details')->toArray();
            $updated = $ieLayout->update($ieLayoutData);

            $efficiency = $ieLayout->efficiency_constant;

            if (isset($data['details'])) {
                $detailIds = collect($data['details'])->pluck('id')->filter()->toArray();
                
                // Delete missing details
                $ieLayout->details()->whereNotIn('id', $detailIds)->delete();

                // Update or create details
                foreach ($data['details'] as $detail) {
                    $detail = $this->calculateMetrics($detail, $efficiency);
                    $detail['ie_layout_id'] = $ieLayout->id;
                    $detail['updated_by_id'] = $data['updated_by_id'] ?? null;
                    
                    // Clean up for DB
                    $dbData = collect($detail)->only((new TimeStudy())->getFillable())->toArray();

                    if (isset($detail['id'])) {
                        TimeStudy::where('id', $detail['id'])->update($dbData);
                    } else {
                        $dbData['created_by_id'] = $data['updated_by_id'] ?? null;
                        TimeStudy::create($dbData);
                    }
                }
            }

            return $updated;
        });
    }

    /**
     * Calculate engineering metrics based on user formulas.
     */
    protected function calculateMetrics(array $detail, $efficiency): array
    {
        // 1. Handle Operation (Create if doesn't exist)
        if (isset($detail['operation_name']) && empty($detail['operation_id'])) {
            $op = \App\Features\IeLayout\Models\Operation::firstOrCreate(
                ['name' => $detail['operation_name']],
                [
                    'code' => strtoupper(substr($detail['operation_name'], 0, 3)) . rand(100, 999), 
                    'machine_type' => $detail['machine_type'] ?? 'S',
                    'sequence' => 0
                ]
            );
            $detail['operation_id'] = $op->id;
        }

        $machineType = $detail['machine_type'] ?? '';
        $posHandling = $detail['handling_position_value'] ?? 0;
        $sewLength = $detail['length'] ?? 0;

        // 1. Machine Turn
        $turn = 0;
        $mt = strtoupper($machineType);
        if ($mt === 'O/L') $turn = 0.125;
        elseif (in_array($mt, ['S', 'C', 'BT', 'BH', 'O'])) $turn = 0.158;
        elseif ($mt === 'S/M') $turn = 0.369;
        elseif ($mt === 'IRON') $turn = 0.048;
        else $turn = $detail['machine_turn'] ?? 0;

        $detail['machine_turn'] = $turn;

        $stdTime = ($posHandling > 0) ? ($sewLength * $turn) + $posHandling : 0;
        $stdTime = ceil($stdTime * 100) / 100; // Round up to 2 decimals
        $detail['std_time'] = $stdTime;

        // 3. Standard Industry Formulas
        $mp = $detail['man_power'] ?? 1;
        $detail['smv'] = ($stdTime > 0) ? $stdTime / 60 : 0;
        $targetHour = ($stdTime > 0) ? (3600 * $efficiency * $mp) / $stdTime : 0;
        $detail['target_hour'] = $targetHour;

        // 4. Target/Day
        $detail['target_day'] = $targetHour * 8;

        return $detail;
    }


    /**
     * Delete an IE layout and its cascading details.
     */
    public function delete(int $id): bool
    {
        $ieLayout = IeLayout::findOrFail($id);
        return $ieLayout->delete();
    }
}
