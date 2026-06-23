<?php

namespace App\Features\LayingPlanning\Seeders;

use Illuminate\Database\Seeder;
use App\Features\LayingPlanning\Models\LayingPlanning;
use App\Features\LayingPlanning\Models\LayingPlanningType;
use App\Features\LayingPlanning\Models\LayingPlanningSize;
use App\Features\Reference\Models\Lot;
use App\Features\Reference\Models\Color;
use App\Features\Reference\Models\Fabric;
use App\Features\Reference\Models\Size;

class LayingPlanningSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed tipe laying planning (master data internal, buat jika belum ada)
        $typePrimary = LayingPlanningType::firstOrCreate(
            ['type' => 'BODY'],
            ['description' => 'Primary Planning']
        );

        $typeSecondary = LayingPlanningType::firstOrCreate(
            ['type' => 'COMBINASI'],
            ['description' => 'Planning for support the Primary Planning']
        );

        // 2. Ambil data relasi dari tabel yang sudah di-seed sebelumnya
        // Lot, Color, Fabric → sudah di-seed via ColorFabricSeeder
        $lot    = Lot::where('lot_number', '00')->firstOrFail();
        $color  = Color::where('code', 'BLK-001')->firstOrFail();
        $fabric = Fabric::where('standard_content', '99 Polyester Blue')->firstOrFail();

        $glNumber = $lot->lot_code;

        // Format: CTA-LP-{gl_number}-{color_code}-{YYMM}-{NNN}
        // date('ym') → 2 digit tahun + 2 digit bulan, contoh: 2606 = Juni 2026
        $prefix = 'CTA-LP-' . $glNumber . '-' . $color->code . '-' . date('ym');

        // Hitung existing records dengan prefix ini untuk auto running number
        $lastCount    = LayingPlanning::where('serial_number', 'like', $prefix . '-%')->count();
        $runningNumber = str_pad($lastCount + 1, 3, '0', STR_PAD_LEFT); // 001, 002, 003, ...

        $serialNumber = $prefix . '-' . $runningNumber;

        // Size → sudah di-seed via SizeSeeder
        $sizeS = Size::where('size', 'S')->firstOrFail();
        $sizeM = Size::where('size', 'M')->firstOrFail();
        $sizeL = Size::where('size', 'L')->firstOrFail();

        // 3. Buat Primary Laying Planning
        // UUID di-generate otomatis oleh HasUuids trait saat ->create() dipanggil
        $primary = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialNumber],
            [
                'lot_id'                    => $lot->id,           // UUID string dari tabel lots
                'laying_planning_type_id'   => $typePrimary->id,   // UUID string dari laying_planning_types
                'laying_planning_parent_id' => null,                // Primary tidak punya parent
                'color_id'                  => $color->id,          // UUID string dari tabel colors
                'fabric_id'                 => $fabric->id,         // UUID string dari tabel fabrics
                'plan_date'                 => '2026-07-01',
                'fabric_pattern'            => 'SOLID',
                'is_combine'                => false,
            ]
        );

        // 4. Attach sizes ke primary laying planning via pivot LayingPlanningSize
        foreach ([
            ['model' => $sizeS, 'qty' => 100],
            ['model' => $sizeM, 'qty' => 200],
            ['model' => $sizeL, 'qty' => 150],
        ] as $entry) {
            LayingPlanningSize::firstOrCreate(
                [
                    'laying_planning_id' => $primary->id,       // UUID dari laying_plannings
                    'size_id'            => $entry['model']->id, // UUID dari tabel sizes
                ],
                ['order_qty' => $entry['qty']]
            );
        }

        // 5. Buat Secondary Laying Planning (child dari primary)
        // Serial number secondary = prefix yang sama + suffix '-S' + running number 3 digit
        $prefixSecondary      = $prefix . '-S';
        $lastCountSecondary   = LayingPlanning::where('serial_number', 'like', $prefixSecondary . '-%')->count();
        $runningNumberSecondary = str_pad($lastCountSecondary + 1, 3, '0', STR_PAD_LEFT);
        $serialNumberSecondary  = $prefixSecondary . '-' . $runningNumberSecondary;

        $secondary = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialNumberSecondary],
            [
                'lot_id'                    => $lot->id,
                'laying_planning_type_id'   => $typeSecondary->id,
                'laying_planning_parent_id' => $primary->id,   // UUID dari primary di atas
                'color_id'                  => $color->id,
                'fabric_id'                 => $fabric->id,
                'plan_date'                 => '2026-07-02',
                'fabric_pattern'            => 'STRIPE',
                'is_combine'                => false,
            ]
        );

        // 6. Attach sizes ke secondary laying planning
        foreach ([
            ['model' => $sizeM, 'qty' => 50],
            ['model' => $sizeL, 'qty' => 75],
        ] as $entry) {
            LayingPlanningSize::firstOrCreate(
                [
                    'laying_planning_id' => $secondary->id,
                    'size_id'            => $entry['model']->id,
                ],
                ['order_qty' => $entry['qty']]
            );
        }
    }
}
