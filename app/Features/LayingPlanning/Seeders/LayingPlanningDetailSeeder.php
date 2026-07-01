<?php

namespace App\Features\LayingPlanning\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use App\Features\LayingPlanning\Models\LayingPlanning;
use App\Features\LayingPlanning\Models\LayingPlanningDetail;
use App\Features\LayingPlanning\Models\LayingPlanningDetailType;
use App\Features\LayingPlanning\Services\LayingPlanningDetailService;
use App\Features\Reference\Models\Size;
use App\Models\User;

class LayingPlanningDetailSeeder extends Seeder
{
    public function run(): void
    {
        // Login sebagai admin supaya Auth::id() di service valid
        $admin = User::where('email', 'admin@test.com')->firstOrFail();
        Auth::loginUsingId($admin->id);

        $service = app(LayingPlanningDetailService::class);

        // Ambil LP pertama (Basic / Normal: Lot 04, Red Sport, Nylon, SOLID)
        $lp = LayingPlanning::whereHas('lot', fn($q) => $q->where('lot_number', '04'))
            ->whereHas('color', fn($q) => $q->where('code', 'RED'))
            ->whereHas('fabric', fn($q) => $q->where('standard_content', '80 Nylon 20 Elastane'))
            ->where('fabric_pattern', 'SOLID')
            ->firstOrFail();

        // Ambil master size
        $sizeS = Size::where('size', 'S')->firstOrFail();
        $sizeM = Size::where('size', 'M')->firstOrFail();
        $sizeL = Size::where('size', 'L')->firstOrFail();

        // Ambil master type
        $typeNormal = LayingPlanningDetailType::where('detail_type', 'NORMAL')->firstOrFail();
        $typeBinding = LayingPlanningDetailType::where('detail_type', 'BINDING')->firstOrFail();
        $typeInterlining = LayingPlanningDetailType::where('detail_type', 'INTERLINING')->firstOrFail();
        $typeTrim = LayingPlanningDetailType::where('detail_type', 'TRIM')->firstOrFail();

        // Definisi template marker (kode + panjang + ratio size, fixed per marker_code)
        $markers = [
            'BULK-A' => [
                'marker_yard' => 12,
                'marker_inch' => 6.5,
                'allowance_inch' => 2,
                'sizes' => [
                    ['size' => $sizeS, 'ratio_per_size' => 2],
                    ['size' => $sizeM, 'ratio_per_size' => 1],
                ],
            ],
            'BULK-B' => [
                'marker_yard' => 11,
                'marker_inch' => 4.25,
                'allowance_inch' => 1,
                'sizes' => [
                    ['size' => $sizeM, 'ratio_per_size' => 2],
                    ['size' => $sizeL, 'ratio_per_size' => 1],
                ],
            ],
            'BULK-C' => [
                'marker_yard' => 10,
                'marker_inch' => 8.75,
                'allowance_inch' => 2,
                'sizes' => [
                    ['size' => $sizeS, 'ratio_per_size' => 1],
                    ['size' => $sizeM, 'ratio_per_size' => 2],
                    ['size' => $sizeL, 'ratio_per_size' => 1],
                ],
            ],
        ];

        // Cutting sessions per marker
        // Same marker_code bisa muncul di multiple sessions (table_number urut 1,2,3 dst)
        // Hanya layer_qty yang bervariasi per sesi
        $sessions = [
            ['marker_code' => 'BULK-A', 'layer_qty' => 50, 'type' => $typeNormal, 'is_pilot_run' => true],
            ['marker_code' => 'BULK-A', 'layer_qty' => 45, 'type' => $typeNormal, 'is_pilot_run' => false],
            ['marker_code' => 'BULK-A', 'layer_qty' => 40, 'type' => $typeNormal, 'is_pilot_run' => false],
            ['marker_code' => 'BULK-B', 'layer_qty' => 55, 'type' => $typeBinding, 'is_pilot_run' => false],
            ['marker_code' => 'BULK-B', 'layer_qty' => 48, 'type' => $typeBinding, 'is_pilot_run' => false],
            ['marker_code' => 'BULK-C', 'layer_qty' => 35, 'type' => $typeInterlining, 'is_pilot_run' => false],
            ['marker_code' => 'BULK-C', 'layer_qty' => 30, 'type' => $typeTrim, 'is_pilot_run' => false],
        ];

        foreach ($sessions as $session) {
            $marker = $markers[$session['marker_code']];

            // Idempotency: skip kalau session identik sudah ada
            $exists = LayingPlanningDetail::where('laying_planning_id', $lp->id)
                ->where('marker_code', $session['marker_code'])
                ->where('layer_qty', $session['layer_qty'])
                ->exists();

            if ($exists) {
                continue;
            }

            $service->create($lp->id, [
                'laying_planning_detail_type_id' => $session['type']->id,
                'layer_qty' => $session['layer_qty'],
                'marker_code' => $session['marker_code'],
                'marker_yard' => $marker['marker_yard'],
                'marker_inch' => $marker['marker_inch'],
                'allowance_inch' => $marker['allowance_inch'],
                'is_pilot_run' => $session['is_pilot_run'],
                'sizes' => array_map(fn($s) => [
                    'size_id' => $s['size']->id,
                    'ratio_per_size' => $s['ratio_per_size'],
                ], $marker['sizes']),
            ]);
        }
    }
}
