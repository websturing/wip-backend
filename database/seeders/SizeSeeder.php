<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Features\Reference\Models\Size;

class SizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

        foreach ($sizes as $size) {
            Size::firstOrCreate(['size' => $size]);
        }
    }
}
