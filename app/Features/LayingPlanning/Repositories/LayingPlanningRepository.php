<?php

namespace App\Features\LayingPlanning\Repositories;

use App\Features\LayingPlanning\Models\LayingPlanning;
use App\Features\LayingPlanning\Models\LayingPlanningPart;
use Illuminate\Support\Collection;

class LayingPlanningRepository
{
    public function getAll(): Collection
    {
        return LayingPlanning::with([
            'layingPlanningType',
            'lot.glGroup',
            'color',
            'fabric',
            'sizeDetails',
            'parts'
        ])->latest()->orderBy('id', 'desc')->get();
    }

    public function paginate(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 20;
        $search = $filters['search'] ?? null;
        $order = isset($filters['order']) && strtolower($filters['order']) === 'asc' ? 'asc' : 'desc';

        $query = LayingPlanning::with([
            'layingPlanningType',
            'lot.glGroup',
            'color',
            'fabric',
            'sizeDetails',
            'combineGroup',
            'parts'
        ]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%")
                  ->orWhereHas('lot', function ($sq) use ($search) {
                      $sq->where('lot_code', 'like', "%{$search}%")
                        ->orWhere('lot_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('color', function ($sq) use ($search) {
                      $sq->where('standard_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($order === 'asc') {
            return $query->orderBy('created_at', 'asc')->orderBy('id', 'asc')->paginate($perPage);
        }

        return $query->latest()->orderBy('id', 'desc')->paginate($perPage);
    }

    public function findById(string $id): ?LayingPlanning
    {
        $layingPlanning = LayingPlanning::with([
            'layingPlanningType',
            'lot.glGroup',
            'color',
            'fabric',
            'sizeDetails',
            'parent',
            'children',
            'combineGroup',
            'parts'
        ])->find($id);

        if ($layingPlanning) {
            $groupCodes = $layingPlanning->parts->pluck('item_part_group_code')->filter()->unique();
            if ($groupCodes->isNotEmpty()) {
                $groupParts = LayingPlanningPart::whereIn('item_part_group_code', $groupCodes)
                    ->get();
                $layingPlanning->setRelation('groupParts', $groupParts);
            } else {
                $layingPlanning->setRelation('groupParts', collect());
            }
        }

        return $layingPlanning;
    }

    public function create(array $data): LayingPlanning
    {
        return LayingPlanning::create($data);
    }

    public function update(string $id, array $data): bool
    {
        $record = LayingPlanning::findOrFail($id);
        return $record->update($data);
    }

    public function delete(string $id): bool
    {
        $record = LayingPlanning::findOrFail($id);
        return $record->delete();
    }
}
