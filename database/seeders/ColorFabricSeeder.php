<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Features\Reference\Models\GlGroup;
use App\Features\Reference\Models\Customer;
use App\Features\Reference\Models\Lot;
use App\Features\Reference\Models\Color;
use App\Features\Reference\Models\ColorAlias;
use App\Features\Reference\Models\Fabric;
use App\Features\Reference\Models\FabricAlias;

class ColorFabricSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get or create a Customer
        $customer = Customer::firstOrCreate(
            ['name' => 'Nike'],
            ['country' => 'USA']
        );

        // 2. Get or create a GL Group
        $glGroup = GlGroup::firstOrCreate(
            ['gl_number' => 'GL-66671'],
            ['customer_id' => $customer->id]
        );

        // 3. Create Master Color
        $color = Color::firstOrCreate([
            'gl_id' => $glGroup->id,
            'standard_name' => 'Black',
            'code' => 'BLK-001'
        ]);

        // 4. Create Color Aliases
        ColorAlias::firstOrCreate([
            'color_id' => $color->id,
            'department' => 'warehouse',
            'alias_name' => 'black'
        ]);
        
        ColorAlias::firstOrCreate([
            'color_id' => $color->id,
            'department' => 'cutting',
            'alias_name' => 'black 2'
        ]);

        // 5. Create Master Fabric
        $fabric = Fabric::firstOrCreate([
            'gl_id' => $glGroup->id,
            'standard_content' => '99 Polyester Blue'
        ]);

        // 6. Create Fabric Aliases
        FabricAlias::firstOrCreate([
            'fabric_id' => $fabric->id,
            'department' => 'warehouse',
            'alias_content' => '99 pollyester blue'
        ]);

        FabricAlias::firstOrCreate([
            'fabric_id' => $fabric->id,
            'department' => 'cutting',
            'alias_content' => '99 polyester blue2'
        ]);

        // 7. Update or create a Lot and assign color & fabric
        Lot::updateOrCreate(
            [
                'gl_id' => $glGroup->id,
                'lot_number' => '00'
            ],
            [
                'gmt_qty' => 1000,
                'is_cancelled' => false,
                'style_no' => 'N-SPORT-200',
                'brand' => 'Nike Pro',
                'color_id' => $color->id,
                'fabric_id' => $fabric->id
            ]
        );

        Lot::updateOrCreate(
            [
                'gl_id' => $glGroup->id,
                'lot_number' => '01'
            ],
            [
                'gmt_qty' => 1500,
                'is_cancelled' => false,
                'style_no' => 'N-SPORT-200',
                'brand' => 'Nike Pro',
                'color_id' => $color->id,
                'fabric_id' => $fabric->id
            ]
        );
    }
}
