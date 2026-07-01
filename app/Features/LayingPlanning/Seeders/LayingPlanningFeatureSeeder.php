<?php

namespace App\Features\LayingPlanning\Seeders;

use Illuminate\Database\Seeder;

class LayingPlanningFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LayingPlanningReferenceSeeder::class,
            LayingPlanningSeeder::class,
            LayingPlanningDetailTypeSeeder::class,
            LayingPlanningDetailSeeder::class,
        ]);
    }
}
