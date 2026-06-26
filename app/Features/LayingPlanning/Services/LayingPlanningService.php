<?php

namespace App\Features\LayingPlanning\Services;

use App\Features\LayingPlanning\Repositories\LayingPlanningRepository;
use App\Features\LayingPlanning\Models\LayingPlanning;
use App\Features\LayingPlanning\Models\LayingPlanningSize;
use App\Features\LayingPlanning\Models\LayingPlanningCombine;
use App\Features\LayingPlanning\Models\LayingPlanningPart;
use App\Features\Reference\Models\Lot;
use App\Features\Reference\Models\Color;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LayingPlanningService
{
    protected LayingPlanningRepository $repository;

    public function __construct(LayingPlanningRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function findById(string $id): ?LayingPlanning
    {
        return $this->repository->findById($id);
    }

    public function createBulk(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $isCombine = collect($data)->contains(function ($item) {
                return isset($item['is_combine']) && $item['is_combine'] == true;
            });

            $combineId = null;

            if ($isCombine) {
                $lotIds = collect($data)->pluck('lot_id')->unique()->toArray();
                $colorId = $data[0]['color_id'];

                $combineNumber = $this->generateCombineNumber($lotIds, $colorId);

                $combine = LayingPlanningCombine::create([
                    'combine_number' => $combineNumber,
                ]);

                $combineId = $combine->id;
            }

            $createdPlannings = [];
            foreach ($data as $item) {
                if ($combineId) {
                    $item['laying_planning_combine_id'] = $combineId;
                    $item['is_combine'] = true;
                }

                $createdPlannings[] = $this->createSingle($item);
            }

            return $createdPlannings;
        });
    }

    protected function createSingle(array $data): LayingPlanning
    {
        // 1. Generate serial number otomatis
        $data['serial_number'] = $this->generateSerialNumber(
            $data['lot_id'],
            $data['color_id'],
            $data['laying_planning_parent_id'] ?? null
        );

        // 2. Ambil list sizes dari data dan hapus dari parameter create utama
        $sizes = $data['sizes'] ?? [];
        unset($data['sizes']);

        // 3. Ambil list parts dari data dan hapus dari parameter create utama
        $parts = $data['parts'] ?? [];
        unset($data['parts']);

        // 4. Simpan Laying Planning utama
        $layingPlanning = $this->repository->create($data);

        // 5. Hubungkan detail sizes ke database
        foreach ($sizes as $size) {
            LayingPlanningSize::create([
                'laying_planning_id' => $layingPlanning->id,
                'size_id' => $size['size_id'],
                'order_qty' => $size['order_qty'],
            ]);
        }

        // 6. Hubungkan detail parts jika ada
        if (!empty($parts)) {
            $isSetItem = (bool) ($layingPlanning->is_set_item ?? false);
            $defaultGrouping = $isSetItem ? (string) Str::uuid() : null;
            foreach ($parts as $part) {
                LayingPlanningPart::create([
                    'laying_planning_id'   => $layingPlanning->id,
                    'item_part'            => $part['item_part'],
                    'item_part_group_code' => $isSetItem 
                        ? (!empty($part['item_part_group_code']) ? $part['item_part_group_code'] : $defaultGrouping)
                        : null,
                ]);
            }
        }

        // 7. Kembalikan data lengkap beserta relasinya
        return $this->repository->findById($layingPlanning->id);
    }

    public function generateCombineNumber(array $lotIds, string $colorId): string
    {
        $lotCodes = [];
        foreach ($lotIds as $lotId) {
            $lot = Lot::findOrFail($lotId);
            $lotCodes[] = $lot->lot_code;
        }

        $color = Color::findOrFail($colorId);

        // Gabungkan lot code menggunakan logic format khusus
        $lotsString = $this->formatCombineLots($lotCodes);

        $prefix = 'CTA-LP-CMB-GL-' . $lotsString . '-' . $color->code . '-' . date('ym');

        // Hitung baris yang memiliki prefix sejenis untuk running number
        $lastCount = LayingPlanningCombine::where('combine_number', 'like', $prefix . '-%')->count();
        $runningNumber = str_pad($lastCount + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . '-' . $runningNumber;
    }

    protected function formatCombineLots(array $lotCodes): string
    {
        if (empty($lotCodes)) {
            return '';
        }

        $parts = [];
        foreach ($lotCodes as $code) {
            $pos = strrpos($code, '-');
            if ($pos !== false) {
                $prefix = substr($code, 0, $pos);
                $suffix = substr($code, $pos + 1);
                $parts[] = [
                    'prefix' => $prefix,
                    'suffix' => $suffix,
                    'original' => $code
                ];
            } else {
                $parts[] = [
                    'prefix' => $code,
                    'suffix' => '',
                    'original' => $code
                ];
            }
        }

        $prefixes = array_unique(array_column($parts, 'prefix'));
        if (count($prefixes) === 1) {
            // Semua prefix (5 angka di awal) sama, kompres dengan suffix
            $commonPrefix = $prefixes[0];
            $suffixes = array_column($parts, 'suffix');
            return $commonPrefix . '-' . implode('/', $suffixes);
        }

        // Jika berbeda, tuliskan semuanya dipisahkan dengan '/'
        return implode('/', $lotCodes);
    }

    public function updateBulk(array $data): bool
    {
        return DB::transaction(function () use ($data) {
            foreach ($data as $item) {
                $id = $item['id'];
                unset($item['id']);
                $this->updateSingle($id, $item);
            }
            return true;
        });
    }

    public function updateSingle(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            // Ambil sizes jika dikirimkan
            $sizes = null;
            if (array_key_exists('sizes', $data)) {
                $sizes = $data['sizes'];
                unset($data['sizes']);
            }

            // Ambil parts jika dikirimkan
            $parts = null;
            if (array_key_exists('parts', $data)) {
                $parts = $data['parts'];
                unset($data['parts']);
            }

            // Update main record
            $this->repository->update($id, $data);
            $layingPlanning = $this->repository->findById($id);

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

            // Sync parts jika dikirimkan di payload
            if ($parts !== null) {
                LayingPlanningPart::where('laying_planning_id', $id)->delete();
                $isSetItem = (bool) ($layingPlanning->is_set_item ?? false);
                $defaultGrouping = $isSetItem ? (string) Str::uuid() : null;
                foreach ($parts as $part) {
                    LayingPlanningPart::create([
                        'laying_planning_id'   => $id,
                        'item_part'            => $part['item_part'],
                        'item_part_group_code' => $isSetItem 
                            ? (!empty($part['item_part_group_code']) ? $part['item_part_group_code'] : $defaultGrouping)
                            : null,
                    ]);
                }
            } elseif (!$layingPlanning->is_set_item) {
                // Jika $parts tidak dikirim di payload, tapi is_set_item diubah/bernilai false,
                // pastikan semua part yang ada diubah group_code-nya menjadi null.
                LayingPlanningPart::where('laying_planning_id', $id)->update(['item_part_group_code' => null]);
            }

            return true;
        });
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Generate serial number based on parameters:
     * Format: CTA-LP-{gl_number/lot_code}-{color_code}-{YYMM}-{NNN}
     * Secondary (with parent) inserts '-SUP' before the date suffix.
     */
    public function generateSerialNumber(string $lotId, string $colorId, ?string $parentId): string
    {
        $lot = Lot::findOrFail($lotId);
        $color = Color::findOrFail($colorId);

        $glNumber = $lot->lot_code; // format: gl_number-lot_number

        $prefix = 'CTA-LP-' . $glNumber . '-' . $color->code;

        if ($parentId) {
            $prefix .= '-SUP';
        }

        $prefix .= '-' . date('ym');

        // Hitung semua record laying planning di bulan berjalan untuk running number global bulanan
        $monthPattern = '%-' . date('ym') . '-%';
        $lastCount = LayingPlanning::where('serial_number', 'like', $monthPattern)->count();
        $runningNumber = str_pad($lastCount + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . '-' . $runningNumber;
    }
}
