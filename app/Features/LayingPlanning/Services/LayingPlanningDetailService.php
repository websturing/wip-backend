<?php

namespace App\Features\LayingPlanning\Services;

use App\Features\LayingPlanning\Repositories\LayingPlanningDetailRepository;
use App\Features\LayingPlanning\Models\LayingPlanningDetail;
use App\Features\LayingPlanning\Models\LayingPlanningDetailSize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class LayingPlanningDetailService
{
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

            $data['laying_planning_id'] = $layingPlanningId;
            $data['table_number'] = $this->generateNextTableNumber($layingPlanningId);
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $detail = $this->repository->create($data);

            foreach ($sizes as $size) {
                LayingPlanningDetailSize::create([
                    'laying_planning_detail_id' => $detail->id,
                    'size_id' => $size['size_id'],
                    'ratio_per_size' => $size['ratio_per_size'],
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

            $data['updated_by'] = Auth::id();

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

            return true;
        });
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }
}
