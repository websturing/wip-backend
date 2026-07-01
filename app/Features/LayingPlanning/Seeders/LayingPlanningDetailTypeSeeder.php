<?php

namespace App\Features\LayingPlanning\Seeders;

use Illuminate\Database\Seeder;
use App\Features\LayingPlanning\Models\LayingPlanningDetailType;

class LayingPlanningDetailTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['detail_type' => 'NORMAL', 'description' => 'Standard cutting session'],
            ['detail_type' => 'BINDING', 'description' => 'Cutting for binding tape pieces'],
            ['detail_type' => 'INTERLINING', 'description' => 'Cutting for interlining pieces'],
            ['detail_type' => 'REPLACEMENT', 'description' => 'Replacement cut for defective pieces'],
            ['detail_type' => 'TRIM', 'description' => 'Cutting for trim pieces'],
        ];

        foreach ($types as $row) {
            LayingPlanningDetailType::firstOrCreate(
                ['detail_type' => $row['detail_type']],
                ['description' => $row['description']]
            );
        }
    }
}
