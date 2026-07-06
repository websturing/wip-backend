<?php

namespace App\Features\LayingPlanning\Services;

use App\Features\LayingPlanning\Repositories\LayingPlanningDetailRepository;
use App\Features\LayingPlanning\Models\LayingPlanningDetail;
use App\Features\LayingPlanning\Models\LayingPlanningDetailSize;
use App\Features\LayingPlanning\Models\LayingPlanningDetailMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class LayingPlanningDetailService
{
    const MAX_DUPLICATE_COUNT = 10;

    protected LayingPlanningDetailRepository $repository;

    public function __construct(LayingPlanningDetailRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllByLayingPlanning(string $layingPlanningId): Collection
    {
        return $this->repository->getAllByLayingPlanning($layingPlanningId);
    }

    public function findById(string $id): ?LayingPlanningDetail
    {
        return $this->repository->findById($id);
    }

    public function create(string $layingPlanningId, array $data): LayingPlanningDetail
    {
        return DB::transaction(function () use ($layingPlanningId, $data) {
            $sizes = $data['sizes'] ?? [];
            unset($data['sizes']);

            $materials = $data['materials'] ?? [];
            unset($data['materials']);

            $data['laying_planning_id'] = $layingPlanningId;
            $data['table_number'] = $this->generateNextTableNumber($layingPlanningId);

            $detail = $this->repository->create($data);

            foreach ($sizes as $size) {
                LayingPlanningDetailSize::create([
                    'laying_planning_detail_id' => $detail->id,
                    'size_id' => $size['size_id'],
                    'ratio_per_size' => $size['ratio_per_size'],
                ]);
            }

            foreach ($materials as $material) {
                LayingPlanningDetailMaterial::create([
                    'laying_planning_detail_id' => $detail->id,
                    'laying_planning_detail_type_id' => $material['laying_planning_detail_type_id'],
                    'value_per_layer' => $material['value_per_layer'],
                    'unit' => $material['unit'],
                    'color_id' => $material['color_id'] ?? null,
                    'fabric_id' => $material['fabric_id'] ?? null,
                    'properties' => $material['properties'] ?? null,
                ]);
            }

            return $this->repository->findById($detail->id);
        });
    }

    protected function generateNextTableNumber(string $layingPlanningId): int
    {
        $max = LayingPlanningDetail::where('laying_planning_id', $layingPlanningId)
            ->max('table_number');

        return ((int) $max) + 1;
    }

    public function update(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $sizes = null;
            if (array_key_exists('sizes', $data)) {
                $sizes = $data['sizes'];
                unset($data['sizes']);
            }

            $materials = null;
            if (array_key_exists('materials', $data)) {
                $materials = $data['materials'];
                unset($data['materials']);
            }

            $this->repository->update($id, $data);

            if ($sizes !== null) {
                $existingSizes = LayingPlanningDetailSize::where('laying_planning_detail_id', $id)
                    ->get()
                    ->keyBy('size_id');

                $newSizeIds = collect($sizes)->pluck('size_id')->toArray();

                $sizesToDelete = $existingSizes->keys()->diff($newSizeIds);
                if ($sizesToDelete->isNotEmpty()) {
                    LayingPlanningDetailSize::where('laying_planning_detail_id', $id)
                        ->whereIn('size_id', $sizesToDelete)
                        ->delete();
                }

                foreach ($sizes as $sizeData) {
                    $sizeId = $sizeData['size_id'];
                    $ratio = $sizeData['ratio_per_size'];

                    if ($existingSizes->has($sizeId)) {
                        $existingRecord = $existingSizes->get($sizeId);
                        if ($existingRecord->ratio_per_size != $ratio) {
                            $existingRecord->update(['ratio_per_size' => $ratio]);
                        }
                    } else {
                        LayingPlanningDetailSize::create([
                            'laying_planning_detail_id' => $id,
                            'size_id' => $sizeId,
                            'ratio_per_size' => $ratio,
                        ]);
                    }
                }
            }

            if ($materials !== null) {
                $existingMaterials = LayingPlanningDetailMaterial::where('laying_planning_detail_id', $id)
                    ->get()
                    ->keyBy('laying_planning_detail_type_id');

                $newTypeIds = collect($materials)->pluck('laying_planning_detail_type_id')->toArray();

                $materialsToDelete = $existingMaterials->keys()->diff($newTypeIds);
                if ($materialsToDelete->isNotEmpty()) {
                    LayingPlanningDetailMaterial::where('laying_planning_detail_id', $id)
                        ->whereIn('laying_planning_detail_type_id', $materialsToDelete)
                        ->delete();
                }

                foreach ($materials as $material) {
                    $typeId = $material['laying_planning_detail_type_id'];

                    if ($existingMaterials->has($typeId)) {
                        $existingRecord = $existingMaterials->get($typeId);
                        $existingRecord->update([
                            'value_per_layer' => $material['value_per_layer'],
                            'unit' => $material['unit'],
                            'color_id' => $material['color_id'] ?? null,
                            'fabric_id' => $material['fabric_id'] ?? null,
                            'properties' => $material['properties'] ?? null,
                        ]);
                    } else {
                        LayingPlanningDetailMaterial::create([
                            'laying_planning_detail_id' => $id,
                            'laying_planning_detail_type_id' => $typeId,
                            'value_per_layer' => $material['value_per_layer'],
                            'unit' => $material['unit'],
                            'color_id' => $material['color_id'] ?? null,
                            'fabric_id' => $material['fabric_id'] ?? null,
                            'properties' => $material['properties'] ?? null,
                        ]);
                    }
                }
            }

            return true;
        });
    }

    public function duplicate(string $layingPlanningId, string $detailId, int $count): Collection
    {
        $source = $this->findById($detailId);

        if (!$source || $source->laying_planning_id !== $layingPlanningId) {
            return collect();
        }

        $sizes = $source->sizes;
        $materials = $source->materials;

        return DB::transaction(function () use ($layingPlanningId, $source, $sizes, $materials, $count) {
            $created = [];

            for ($i = 0; $i < $count; $i++) {
                $detail = $this->repository->create([
                    'laying_planning_id' => $layingPlanningId,
                    'laying_planning_detail_type_id' => $source->laying_planning_detail_type_id,
                    'table_number' => $this->generateNextTableNumber($layingPlanningId),
                    'layer_qty' => $source->layer_qty,
                    'marker_code' => $source->marker_code,
                    'marker_yard' => $source->marker_yard,
                    'marker_inch' => $source->marker_inch,
                    'allowance_inch' => $source->allowance_inch,
                    'is_pilot_run' => false,
                ]);

                foreach ($sizes as $size) {
                    LayingPlanningDetailSize::create([
                        'laying_planning_detail_id' => $detail->id,
                        'size_id' => $size->size_id,
                        'ratio_per_size' => $size->ratio_per_size,
                    ]);
                }

                foreach ($materials as $material) {
                    LayingPlanningDetailMaterial::create([
                        'laying_planning_detail_id' => $detail->id,
                        'laying_planning_detail_type_id' => $material->laying_planning_detail_type_id,
                        'value_per_layer' => $material->value_per_layer,
                        'unit' => $material->unit,
                        'color_id' => $material->color_id,
                        'fabric_id' => $material->fabric_id,
                        'properties' => $material->properties,
                    ]);
                }

                $created[] = $this->repository->findById($detail->id);
            }

            return collect($created);
        });
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }
}
