<?php

namespace App\Features\LayingPlanning\Seeders;

use Illuminate\Database\Seeder;
use App\Features\Reference\Models\GlGroup;
use App\Features\Reference\Models\Customer;
use App\Features\Reference\Models\Lot;
use App\Features\Reference\Models\Color;
use App\Features\Reference\Models\ColorAlias;
use App\Features\Reference\Models\Fabric;
use App\Features\Reference\Models\FabricAlias;

class LayingPlanningReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get or create Customer and GL Group
        $customer = Customer::firstOrCreate(
            ['name' => 'Nike'],
            ['country' => 'USA']
        );

        $glGroup = GlGroup::firstOrCreate(
            ['gl_number' => '66671'],
            ['customer_id' => $customer->id]
        );

        // 2. Seed variative Colors
        $colorNavy = Color::firstOrCreate([
            'gl_id' => $glGroup->id,
            'standard_name' => 'Navy Blue',
            'code' => 'NVY-002'
        ]);
        ColorAlias::firstOrCreate([
            'color_id' => $colorNavy->id,
            'department' => 'warehouse',
            'alias_name' => 'navy'
        ]);

        $colorWhite = Color::firstOrCreate([
            'gl_id' => $glGroup->id,
            'standard_name' => 'White',
            'code' => 'WHT-003'
        ]);
        ColorAlias::firstOrCreate([
            'color_id' => $colorWhite->id,
            'department' => 'warehouse',
            'alias_name' => 'putih'
        ]);

        $colorRed = Color::firstOrCreate([
            'gl_id' => $glGroup->id,
            'standard_name' => 'Red Sport',
            'code' => 'RED-004'
        ]);

        // 3. Seed variative Fabrics
        $fabricCotton = Fabric::firstOrCreate([
            'gl_id' => $glGroup->id,
            'standard_content' => '100 Cotton Combed 30s'
        ]);
        FabricAlias::firstOrCreate([
            'fabric_id' => $fabricCotton->id,
            'department' => 'warehouse',
            'alias_content' => 'cotton combed 30s'
        ]);

        $fabricNylon = Fabric::firstOrCreate([
            'gl_id' => $glGroup->id,
            'standard_content' => '80 Nylon 20 Elastane'
        ]);

        $fabricDryfit = Fabric::firstOrCreate([
            'gl_id' => $glGroup->id,
            'standard_content' => '100 Polyester Dryfit'
        ]);

        // 4. Seed variative Lots referencing the new colors & fabrics
        Lot::updateOrCreate(
            [
                'gl_id' => $glGroup->id,
                'lot_number' => '02'
            ],
            [
                'lot_code' => $glGroup->gl_number . '-02',
                'gmt_qty' => 1200,
                'is_cancelled' => false,
                'style_no' => 'N-SPORT-201',
                'brand' => 'Nike Sportswear',
                'color_id' => $colorNavy->id,
                'fabric_id' => $fabricCotton->id
            ]
        );

        Lot::updateOrCreate(
            [
                'gl_id' => $glGroup->id,
                'lot_number' => '03'
            ],
            [
                'lot_code' => $glGroup->gl_number . '-03',
                'gmt_qty' => 800,
                'is_cancelled' => false,
                'style_no' => 'N-SPORT-202',
                'brand' => 'Nike Running',
                'color_id' => $colorWhite->id,
                'fabric_id' => $fabricDryfit->id
            ]
        );

        Lot::updateOrCreate(
            [
                'gl_id' => $glGroup->id,
                'lot_number' => '04'
            ],
            [
                'lot_code' => $glGroup->gl_number . '-04',
                'gmt_qty' => 2000,
                'is_cancelled' => false,
                'style_no' => 'N-SPORT-203',
                'brand' => 'Nike Fit',
                'color_id' => $colorRed->id,
                'fabric_id' => $fabricNylon->id
            ]
        );
    }
}
