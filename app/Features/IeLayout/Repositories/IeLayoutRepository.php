<?php

namespace App\Features\IeLayout\Repositories;

use App\Features\IeLayout\Models\IeLayout;
use App\Features\IeLayout\Models\TimeStudy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IeLayoutRepository
{
    /**
     * Get all IE layouts with their details and related models.
     */
    public function getAll(): Collection
    {
        return IeLayout::with(['details.operation', 'createdBy', 'updatedBy'])->get();
    }

    /**
     * Find an IE layout by ID with details.
     */
    public function findById(int $id): ?IeLayout
    {
        return IeLayout::with(['details.operation', 'createdBy', 'updatedBy'])->find($id);
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
                    $detail['ie_layout_id'] = $ieLayout->id;
                    $detail['created_by_id'] = $ieLayout->created_by_id;
                    $detail['updated_by_id'] = $ieLayout->updated_by_id;
                    TimeStudy::create($detail);
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

            if (isset($data['details'])) {
                $detailIds = collect($data['details'])->pluck('id')->filter()->toArray();
                
                // Delete missing details
                $ieLayout->details()->whereNotIn('id', $detailIds)->delete();

                // Update or create details
                foreach ($data['details'] as $detail) {
                    $detail['ie_layout_id'] = $ieLayout->id;
                    $detail['updated_by_id'] = $data['updated_by_id'] ?? null;
                    
                    if (isset($detail['id'])) {
                        TimeStudy::where('id', $detail['id'])->update($detail);
                    } else {
                        $detail['created_by_id'] = $data['updated_by_id'] ?? null;
                        TimeStudy::create($detail);
                    }
                }
            }

            return $updated;
        });
    }

    /**
     * Delete an IE layout and its cascading details.
     */
    public function delete(int $id): bool
    {
        $ieLayout = IeLayout::findOrFail($id);
        // Cascading delete should be handled by DB foreign keys, 
        // but we can also handle it here if needed.
        return $ieLayout->delete();
    }
}
