<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Features\Lines\Models\Line;
use Illuminate\Support\Facades\DB;

class LineSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing lines to avoid duplicates
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Line::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $lines = [];

        // Generate A1 - A16
        for ($i = 1; $i <= 16; $i++) {
            $lines[] = ['id' => \Illuminate\Support\Str::uuid(), 'name' => "A$i", 'created_at' => now(), 'updated_at' => now()];
        }

        // Generate A1 NS - A16 NS
        for ($i = 1; $i <= 16; $i++) {
            $lines[] = ['id' => \Illuminate\Support\Str::uuid(), 'name' => "A$i NS", 'created_at' => now(), 'updated_at' => now()];
        }

        // Generate B1 - B16
        for ($i = 1; $i <= 16; $i++) {
            $lines[] = ['id' => \Illuminate\Support\Str::uuid(), 'name' => "B$i", 'created_at' => now(), 'updated_at' => now()];
        }

        // Generate B1 NS - B16 NS
        for ($i = 1; $i <= 16; $i++) {
            $lines[] = ['id' => \Illuminate\Support\Str::uuid(), 'name' => "B$i NS", 'created_at' => now(), 'updated_at' => now()];
        }

        Line::insert($lines);
    }
}
