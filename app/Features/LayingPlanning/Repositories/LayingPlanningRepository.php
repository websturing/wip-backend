<?php

namespace App\Features\LayingPlanning\Repositories;

use App\Features\LayingPlanning\Models\LayingPlanning;
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
            'sizeDetails'
        ])->latest()->get();
    }

    public function paginate(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 20;
        $search = $filters['search'] ?? null;

        $query = LayingPlanning::with([
            'layingPlanningType',
            'lot.glGroup',
            'color',
            'fabric',
            'sizeDetails'
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

        return $query->latest()->paginate($perPage);
    }

    public function findById(string $id): ?LayingPlanning
    {
        return LayingPlanning::with([
            'layingPlanningType',
            'lot.glGroup',
            'color',
            'fabric',
            'sizeDetails',
            'parent',
            'children'
        ])->find($id);
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
