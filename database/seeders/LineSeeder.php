<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Features\Lines\Models\Line;

class LineSeeder extends Seeder
{
    public function run(): void
    {
        $lines = ['A1', 'A2', 'A3', 'A4', 'A5'];
        foreach ($lines as $line) {
            Line::firstOrCreate(['name' => $line]);
        }
    }
}
