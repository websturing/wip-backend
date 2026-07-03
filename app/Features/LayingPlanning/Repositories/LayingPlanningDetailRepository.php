<?php

namespace App\Features\LayingPlanning\Repositories;

use App\Features\LayingPlanning\Models\LayingPlanningDetail;
use Illuminate\Support\Collection;

class LayingPlanningDetailRepository
{
    public function getAllByLayingPlanning(string $layingPlanningId): Collection
    {
        return LayingPlanningDetail::with(['sizes.size', 'materials.type', 'materials.color', 'materials.fabric', 'type', 'createdBy', 'updatedBy'])
            ->where('laying_planning_id', $layingPlanningId)
            ->orderBy('table_number')
            ->get();
    }

    public function findById(string $id): ?LayingPlanningDetail
    {
        return LayingPlanningDetail::with(['sizes.size', 'materials.type', 'materials.color', 'materials.fabric', 'type', 'createdBy', 'updatedBy'])
            ->find($id);
    }

    public function findByIdAndLp(string $id, string $layingPlanningId): ?LayingPlanningDetail
    {
        return LayingPlanningDetail::with(['sizes.size', 'materials.type', 'materials.color', 'materials.fabric', 'type', 'createdBy', 'updatedBy'])
            ->where('laying_planning_id', $layingPlanningId)
            ->find($id);
    }

    public function create(array $data): LayingPlanningDetail
    {
        return LayingPlanningDetail::create($data);
    }

    public function update(string $id, array $data): bool
    {
        return (bool) LayingPlanningDetail::where('id', $id)->update($data);
    }

    public function delete(string $id): bool
    {
        return (bool) LayingPlanningDetail::where('id', $id)->delete();
    }
}
