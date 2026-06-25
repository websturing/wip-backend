<?php

namespace App\Features\LayingPlanning\Seeders;

use Illuminate\Database\Seeder;
use App\Features\LayingPlanning\Models\LayingPlanning;
use App\Features\LayingPlanning\Models\LayingPlanningType;
use App\Features\LayingPlanning\Models\LayingPlanningSize;
use App\Features\LayingPlanning\Models\LayingPlanningCombine;
use App\Features\LayingPlanning\Models\LayingPlanningPart;
use App\Features\Reference\Models\Lot;
use App\Features\Reference\Models\Color;
use App\Features\Reference\Models\Fabric;
use App\Features\Reference\Models\Size;
use App\Features\LayingPlanning\Services\LayingPlanningService;
use Illuminate\Support\Str;

class LayingPlanningSeeder extends Seeder
{
    public function run(): void
    {
        // Dapatkan service untuk generate serial & combine number secara dinamis
        $service = app(LayingPlanningService::class);

        // 1. Seed tipe laying planning
        $typeBody = LayingPlanningType::firstOrCreate(
            ['type' => 'BODY'],
            ['description' => 'Primary Planning for main panels']
        );

        $typeCombinasi = LayingPlanningType::firstOrCreate(
            ['type' => 'COMBINASI'],
            ['description' => 'Planning for supporting the Primary Planning']
        );

        // 2. Ambil master data referensi dari LayingPlanningReferenceSeeder
        $lot02 = Lot::where('lot_number', '02')->firstOrFail();
        $lot03 = Lot::where('lot_number', '03')->firstOrFail();
        $lot04 = Lot::where('lot_number', '04')->firstOrFail();

        $colorNavy = Color::where('code', 'NVY')->firstOrFail();
        $colorWhite = Color::where('code', 'WHT')->firstOrFail();
        $colorRed = Color::where('code', 'RED')->firstOrFail();

        $fabricCotton = Fabric::where('standard_content', '100 Cotton Combed 30s')->firstOrFail();
        $fabricNylon = Fabric::where('standard_content', '80 Nylon 20 Elastane')->firstOrFail();
        $fabricDryfit = Fabric::where('standard_content', '100 Polyester Dryfit')->firstOrFail();
        
        $sizeS = Size::where('size', 'S')->firstOrFail();
        $sizeM = Size::where('size', 'M')->firstOrFail();
        $sizeL = Size::where('size', 'L')->firstOrFail();

        // -------------------------------------------------------------
        // MODEL 1: Basic / Normal Laying Planning
        // -> Lot 04, Warna Red Sport, Bahan Nylon, Pattern SOLID
        // -------------------------------------------------------------
        $serialNormal = $service->generateSerialNumber($lot04->id, $colorRed->id, null);
        $planningNormal = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialNormal],
            [
                'lot_id' => $lot04->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorRed->id,
                'fabric_id' => $fabricNylon->id,
                'plan_date' => '2026-07-01',
                'fabric_pattern' => 'SOLID',
                'is_combine' => false,
                'is_set_item' => false,
            ]
        );

        // Attach sizes
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $planningNormal->id, 'size_id' => $sizeS->id], ['order_qty' => 100]);
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $planningNormal->id, 'size_id' => $sizeM->id], ['order_qty' => 150]);

        // -------------------------------------------------------------
        // MODEL 2: Primary & Secondary (Parent-Child)
        // -> Parent: Lot 02, Warna Navy, Bahan Cotton, Pattern STRIPE (Utama)
        // -> Child:  Lot 02, Warna White, Bahan Dryfit, Pattern SOLID (Kombinasi/Support)
        // -> (Menggunakan Lot/GL yang sama namun beda warna, dipotong terpisah)
        // -------------------------------------------------------------
        $serialPrimary = $service->generateSerialNumber($lot02->id, $colorNavy->id, null);
        $primary = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialPrimary],
            [
                'lot_id' => $lot02->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorNavy->id,
                'fabric_id' => $fabricCotton->id,
                'plan_date' => '2026-07-02',
                'fabric_pattern' => 'STRIPE',
                'is_combine' => false,
                'is_set_item' => false,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $primary->id, 'size_id' => $sizeS->id], ['order_qty' => 200]);
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $primary->id, 'size_id' => $sizeM->id], ['order_qty' => 200]);

        // Secondary / Support planning: ada SUP pada serial_number-nya
        $serialSecondary = $service->generateSerialNumber($lot02->id, $colorWhite->id, $primary->id);
        $secondary = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialSecondary],
            [
                'lot_id' => $lot02->id,
                'laying_planning_type_id' => $typeCombinasi->id,
                'laying_planning_parent_id' => $primary->id,
                'color_id' => $colorWhite->id,
                'fabric_id' => $fabricDryfit->id,
                'plan_date' => '2026-07-02',
                'fabric_pattern' => 'SOLID',
                'is_combine' => false,
                'is_set_item' => false,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $secondary->id, 'size_id' => $sizeS->id], ['order_qty' => 50]);
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $secondary->id, 'size_id' => $sizeM->id], ['order_qty' => 50]);

        // -------------------------------------------------------------
        // MODEL 3: Combine Laying Planning (Lot 02 & Lot 03)
        // -> Warna Navy Blue, Bahan Cotton, Pattern PRINT
        // -------------------------------------------------------------
        $combineNumber = $service->generateCombineNumber([$lot02->id, $lot03->id], $colorNavy->id);
        $combineGroup = LayingPlanningCombine::firstOrCreate(
            ['combine_number' => $combineNumber],
            ['remarks' => 'Combine marker potong untuk 66671-02 dan 66671-03']
        );

        $serialCombine1 = $service->generateSerialNumber($lot02->id, $colorNavy->id, null);
        $combinePlanning1 = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialCombine1],
            [
                'lot_id' => $lot02->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorNavy->id,
                'fabric_id' => $fabricCotton->id,
                'plan_date' => '2026-07-03',
                'fabric_pattern' => 'PRINT',
                'is_combine' => true,
                'laying_planning_combine_id' => $combineGroup->id,
                'is_set_item' => false,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $combinePlanning1->id, 'size_id' => $sizeM->id], ['order_qty' => 300]);
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $combinePlanning1->id, 'size_id' => $sizeL->id], ['order_qty' => 300]);

        $serialCombine2 = $service->generateSerialNumber($lot03->id, $colorNavy->id, null);
        $combinePlanning2 = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialCombine2],
            [
                'lot_id' => $lot03->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorNavy->id,
                'fabric_id' => $fabricCotton->id,
                'plan_date' => '2026-07-03',
                'fabric_pattern' => 'PRINT',
                'is_combine' => true,
                'laying_planning_combine_id' => $combineGroup->id,
                'is_set_item' => false,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $combinePlanning2->id, 'size_id' => $sizeM->id], ['order_qty' => 250]);
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $combinePlanning2->id, 'size_id' => $sizeL->id], ['order_qty' => 250]);

        // -------------------------------------------------------------
        // MODEL 4: Set Item - Satu Planning (Top & Pants)
        // -> Lot 04, Warna Red Sport, Bahan Cotton, Pattern SOLID
        // -------------------------------------------------------------
        $serialSetSingle = $service->generateSerialNumber($lot04->id, $colorRed->id, null);
        $planningSetSingle = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialSetSingle],
            [
                'lot_id' => $lot04->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorRed->id,
                'fabric_id' => $fabricCotton->id,
                'plan_date' => '2026-07-04',
                'fabric_pattern' => 'SOLID',
                'is_combine' => false,
                'is_set_item' => true,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $planningSetSingle->id, 'size_id' => $sizeS->id], ['order_qty' => 120]);
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $planningSetSingle->id, 'size_id' => $sizeM->id], ['order_qty' => 120]);

        $groupToken1 = (string) Str::uuid();
        LayingPlanningPart::firstOrCreate(['laying_planning_id' => $planningSetSingle->id, 'item_part' => 'TOP'], ['item_part_group_code' => $groupToken1]);
        LayingPlanningPart::firstOrCreate(['laying_planning_id' => $planningSetSingle->id, 'item_part' => 'PANTS'], ['item_part_group_code' => $groupToken1]);

        // -------------------------------------------------------------
        // MODEL 5: Set Item - Planning Terpisah (Beda Warna, tetapi Lot/GL sama)
        // -> TOP:   Lot 03, Warna White, Bahan Dryfit, Pattern STRIPE
        // -> PANTS: Lot 03, Warna Navy, Bahan Cotton, Pattern SOLID
        // -------------------------------------------------------------
        // Planning 1: TOP Baju (Lot 03, White, Dryfit) - Tanggal 5
        $serialSetPart1 = $service->generateSerialNumber($lot03->id, $colorWhite->id, null);
        $planningSetPart1 = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialSetPart1],
            [
                'lot_id' => $lot03->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorWhite->id,
                'fabric_id' => $fabricDryfit->id,
                'plan_date' => '2026-07-05',
                'fabric_pattern' => 'STRIPE',
                'is_combine' => false,
                'is_set_item' => true,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $planningSetPart1->id, 'size_id' => $sizeM->id], ['order_qty' => 80]);
        
        // Planning 2: PANTS Celana (Lot 03, Navy, Cotton) - Tanggal 6
        $serialSetPart2 = $service->generateSerialNumber($lot03->id, $colorNavy->id, null);
        $planningSetPart2 = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialSetPart2],
            [
                'lot_id' => $lot03->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorNavy->id,
                'fabric_id' => $fabricCotton->id,
                'plan_date' => '2026-07-06',
                'fabric_pattern' => 'SOLID',
                'is_combine' => false,
                'is_set_item' => true,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $planningSetPart2->id, 'size_id' => $sizeM->id], ['order_qty' => 80]);

        // Hubungkan kedua planning terpisah menggunakan group UUID yang sama
        $groupToken2 = (string) Str::uuid();
        LayingPlanningPart::firstOrCreate(['laying_planning_id' => $planningSetPart1->id, 'item_part' => 'TOP'], ['item_part_group_code' => $groupToken2]);
        LayingPlanningPart::firstOrCreate(['laying_planning_id' => $planningSetPart2->id, 'item_part' => 'PANTS'], ['item_part_group_code' => $groupToken2]);

        // -------------------------------------------------------------
        // SKEMA 6: Combine + Set Item (Satu Planning)
        // -> Combine Lot 02 dan Lot 03, Warna White, Bahan Dryfit, Pattern PRINT
        // -------------------------------------------------------------
        $combineNumber6 = $service->generateCombineNumber([$lot02->id, $lot03->id], $colorWhite->id);
        $combineGroup6 = LayingPlanningCombine::firstOrCreate(
            ['combine_number' => $combineNumber6],
            ['remarks' => 'Combine & Set Item Satu Planning untuk 66671-02 dan 66671-03']
        );

        // Planning 1: Lot 02 (Baju & Celana) - Tanggal 7
        $serialCombineSet1 = $service->generateSerialNumber($lot02->id, $colorWhite->id, null);
        $planningCombineSet1 = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialCombineSet1],
            [
                'lot_id' => $lot02->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorWhite->id,
                'fabric_id' => $fabricDryfit->id,
                'plan_date' => '2026-07-07',
                'fabric_pattern' => 'PRINT',
                'is_combine' => true,
                'laying_planning_combine_id' => $combineGroup6->id,
                'is_set_item' => true,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $planningCombineSet1->id, 'size_id' => $sizeM->id], ['order_qty' => 100]);

        $groupTokenSet1 = (string) Str::uuid();
        LayingPlanningPart::firstOrCreate(['laying_planning_id' => $planningCombineSet1->id, 'item_part' => 'TOP'], ['item_part_group_code' => $groupTokenSet1]);
        LayingPlanningPart::firstOrCreate(['laying_planning_id' => $planningCombineSet1->id, 'item_part' => 'PANTS'], ['item_part_group_code' => $groupTokenSet1]);

        // Planning 2: Lot 03 (Baju & Celana) - Tanggal 7
        $serialCombineSet2 = $service->generateSerialNumber($lot03->id, $colorWhite->id, null);
        $planningCombineSet2 = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialCombineSet2],
            [
                'lot_id' => $lot03->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorWhite->id,
                'fabric_id' => $fabricDryfit->id,
                'plan_date' => '2026-07-07',
                'fabric_pattern' => 'PRINT',
                'is_combine' => true,
                'laying_planning_combine_id' => $combineGroup6->id,
                'is_set_item' => true,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $planningCombineSet2->id, 'size_id' => $sizeM->id], ['order_qty' => 150]);

        $groupTokenSet2 = (string) Str::uuid();
        LayingPlanningPart::firstOrCreate(['laying_planning_id' => $planningCombineSet2->id, 'item_part' => 'TOP'], ['item_part_group_code' => $groupTokenSet2]);
        LayingPlanningPart::firstOrCreate(['laying_planning_id' => $planningCombineSet2->id, 'item_part' => 'PANTS'], ['item_part_group_code' => $groupTokenSet2]);

        // -------------------------------------------------------------
        // SKEMA 7: Combine + Set Item (Planning Terpisah)
        // -> TOP:   Combine Lot 02 & 03, Warna Red, Bahan Nylon, Pattern STRIPE
        // -> PANTS: Combine Lot 02 & 03, Warna Navy, Bahan Cotton, Pattern SOLID
        // -------------------------------------------------------------
        // Group A: Combine untuk Baju (TOP, Red Sport)
        $combineNumber7Top = $service->generateCombineNumber([$lot02->id, $lot03->id], $colorRed->id);
        $combineGroup7Top = LayingPlanningCombine::firstOrCreate(
            ['combine_number' => $combineNumber7Top],
            ['remarks' => 'Combine setelan TOP baju untuk 66671-02/03']
        );

        // Group B: Combine untuk Celana (PANTS, Navy Blue)
        $combineNumber7Pants = $service->generateCombineNumber([$lot02->id, $lot03->id], $colorNavy->id);
        $combineGroup7Pants = LayingPlanningCombine::firstOrCreate(
            ['combine_number' => $combineNumber7Pants],
            ['remarks' => 'Combine setelan PANTS celana untuk 66671-02/03']
        );

        // Baju Lot 02 (Warna Red, Bahan Nylon, Pattern STRIPE) - Tanggal 8
        $serialBajuLot02 = $service->generateSerialNumber($lot02->id, $colorRed->id, null);
        $bajuLot02 = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialBajuLot02],
            [
                'lot_id' => $lot02->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorRed->id,
                'fabric_id' => $fabricNylon->id,
                'plan_date' => '2026-07-08',
                'fabric_pattern' => 'STRIPE',
                'is_combine' => true,
                'laying_planning_combine_id' => $combineGroup7Top->id,
                'is_set_item' => true,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $bajuLot02->id, 'size_id' => $sizeM->id], ['order_qty' => 120]);

        // Celana Lot 02 (Warna Navy, Bahan Cotton, Pattern SOLID) - Tanggal 9
        $serialCelanaLot02 = $service->generateSerialNumber($lot02->id, $colorNavy->id, null);
        $celanaLot02 = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialCelanaLot02],
            [
                'lot_id' => $lot02->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorNavy->id,
                'fabric_id' => $fabricCotton->id,
                'plan_date' => '2026-07-09',
                'fabric_pattern' => 'SOLID',
                'is_combine' => true,
                'laying_planning_combine_id' => $combineGroup7Pants->id,
                'is_set_item' => true,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $celanaLot02->id, 'size_id' => $sizeM->id], ['order_qty' => 120]);

        // Hubungkan Baju Lot 02 & Celana Lot 02 (Setelan Lot 02)
        $uuidSetLot02 = (string) Str::uuid();
        LayingPlanningPart::firstOrCreate(['laying_planning_id' => $bajuLot02->id, 'item_part' => 'TOP'], ['item_part_group_code' => $uuidSetLot02]);
        LayingPlanningPart::firstOrCreate(['laying_planning_id' => $celanaLot02->id, 'item_part' => 'PANTS'], ['item_part_group_code' => $uuidSetLot02]);

        // Baju Lot 03 (Warna Red, Bahan Nylon, Pattern STRIPE) - Tanggal 8
        $serialBajuLot03 = $service->generateSerialNumber($lot03->id, $colorRed->id, null);
        $bajuLot03 = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialBajuLot03],
            [
                'lot_id' => $lot03->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorRed->id,
                'fabric_id' => $fabricNylon->id,
                'plan_date' => '2026-07-08',
                'fabric_pattern' => 'STRIPE',
                'is_combine' => true,
                'laying_planning_combine_id' => $combineGroup7Top->id,
                'is_set_item' => true,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $bajuLot03->id, 'size_id' => $sizeM->id], ['order_qty' => 80]);

        // Celana Lot 03 (Warna Navy, Bahan Cotton, Pattern SOLID) - Tanggal 9
        $serialCelanaLot03 = $service->generateSerialNumber($lot03->id, $colorNavy->id, null);
        $celanaLot03 = LayingPlanning::firstOrCreate(
            ['serial_number' => $serialCelanaLot03],
            [
                'lot_id' => $lot03->id,
                'laying_planning_type_id' => $typeBody->id,
                'laying_planning_parent_id' => null,
                'color_id' => $colorNavy->id,
                'fabric_id' => $fabricCotton->id,
                'plan_date' => '2026-07-09',
                'fabric_pattern' => 'SOLID',
                'is_combine' => true,
                'laying_planning_combine_id' => $combineGroup7Pants->id,
                'is_set_item' => true,
            ]
        );
        LayingPlanningSize::firstOrCreate(['laying_planning_id' => $celanaLot03->id, 'size_id' => $sizeM->id], ['order_qty' => 80]);

        // Hubungkan Baju Lot 03 & Celana Lot 03 (Setelan Lot 03)
        $uuidSetLot03 = (string) Str::uuid();
        LayingPlanningPart::firstOrCreate(['laying_planning_id' => $bajuLot03->id, 'item_part' => 'TOP'], ['item_part_group_code' => $uuidSetLot03]);
        LayingPlanningPart::firstOrCreate(['laying_planning_id' => $celanaLot03->id, 'item_part' => 'PANTS'], ['item_part_group_code' => $uuidSetLot03]);
    }
}
