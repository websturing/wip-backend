<?php

namespace App\Features\LayingPlanning\Services;

use App\Features\LayingPlanning\Repositories\LayingPlanningRepository;
use App\Features\LayingPlanning\Models\LayingPlanning;
use App\Features\LayingPlanning\Models\LayingPlanningSize;
use App\Features\Reference\Models\Lot;
use App\Features\Reference\Models\Color;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LayingPlanningService
{
    protected $repository;

    public function __construct(LayingPlanningRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function findById(string $id)
    {
        return $this->repository->findById($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Generate serial number otomatis
            $data['serial_number'] = $this->generateSerialNumber(
                $data['lot_id'],
                $data['color_id'],
                $data['laying_planning_parent_id'] ?? null
            );

            // 2. Ambil list sizes dari data dan hapus dari parameter create utama
            $sizes = $data['sizes'] ?? [];
            unset($data['sizes']);

            // 3. Simpan Laying Planning utama
            $layingPlanning = $this->repository->create($data);

            // 4. Hubungkan detail sizes ke database
            foreach ($sizes as $size) {
                LayingPlanningSize::create([
                    'laying_planning_id' => $layingPlanning->id,
                    'size_id' => $size['size_id'],
                    'order_qty' => $size['order_qty'],
                ]);
            }

            // 5. Kembalikan data lengkap beserta relasinya
            return $this->repository->findById($layingPlanning->id);
        });
    }

    public function update(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            // Ambil sizes jika dikirimkan
            $sizes = null;
            if (array_key_exists('sizes', $data)) {
                $sizes = $data['sizes'];
                unset($data['sizes']);
            }

            // Update main record
            $this->repository->update($id, $data);

            // Sync sizes dengan pendekatan Intelligent Sync (Upsert/Diff)
            if ($sizes !== null) {
                // 1. Ambil data sizes lama dari DB
                $existingSizes = LayingPlanningSize::where('laying_planning_id', $id)
                    ->get()
                    ->keyBy('size_id'); // Mempermudah lookup di memori

                $newSizeIds = collect($sizes)->pluck('size_id')->toArray();

                // 2. DELETE: Hapus size lama yang tidak ada di request baru
                $sizesToDelete = $existingSizes->keys()->diff($newSizeIds);
                if ($sizesToDelete->isNotEmpty()) {
                    LayingPlanningSize::where('laying_planning_id', $id)
                        ->whereIn('size_id', $sizesToDelete)
                        ->delete();
                }

                // 3. INSERT / UPDATE
                foreach ($sizes as $sizeData) {
                    $sizeId = $sizeData['size_id'];
                    $qty = $sizeData['order_qty'];

                    if ($existingSizes->has($sizeId)) {
                        // Update jika qty berubah
                        $existingRecord = $existingSizes->get($sizeId);
                        if ($existingRecord->order_qty != $qty) {
                            $existingRecord->update(['order_qty' => $qty]);
                        }
                    } else {
                        // Insert jika ukuran baru
                        LayingPlanningSize::create([
                            'laying_planning_id' => $id,
                            'size_id'            => $sizeId,
                            'order_qty'          => $qty,
                        ]);
                    }
                }
            }

            return true;
        });
    }

    public function delete(string $id)
    {
        return $this->repository->delete($id);
    }

    /**
     * Generate serial number based on parameters:
     * Format: CTA-LP-{gl_number/lot_code}-{color_code}-{YYMM}-{NNN}
     * Secondary (with parent) adds suffix '-S' before running number.
     */
    protected function generateSerialNumber(string $lotId, string $colorId, ?string $parentId): string
    {
        $lot = Lot::findOrFail($lotId);
        $color = Color::findOrFail($colorId);

        $glNumber = $lot->lot_code; // format: gl_number-lot_number

        $prefix = 'CTA-LP-' . $glNumber . '-' . $color->code . '-' . date('ym');

        if ($parentId) {
            $prefix .= '-S';
        }

        // Hitung baris yang memiliki prefix sejenis untuk running number
        $lastCount = LayingPlanning::where('serial_number', 'like', $prefix . '-%')->count();
        $runningNumber = str_pad($lastCount + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . '-' . $runningNumber;
    }
}
