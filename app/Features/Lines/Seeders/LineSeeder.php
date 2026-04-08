<?php

namespace App\Features\Lines\Seeders;

use Illuminate\Database\Seeder;
use App\Features\Lines\Models\Line;

class LineSeeder extends Seeder
{
    public function run(): void
    {
        $lines = [
            ['name' => 'Line A1', 'location' => 'Floor 1'],
            ['name' => 'Line A2', 'location' => 'Floor 1'],
            ['name' => 'Line B1', 'location' => 'Floor 2'],
            ['name' => 'Line B2', 'location' => 'Floor 2'],
            ['name' => 'Line C1', 'location' => 'Floor 3'],
        ];

        foreach ($lines as $line) {
            Line::create($line);
        }
    }
}
