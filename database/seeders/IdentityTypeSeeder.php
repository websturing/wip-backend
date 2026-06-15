<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Features\Employee\Models\IdentityType;

class IdentityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'KTP',
                'is_expiration_required' => false,
                'is_active' => true,
            ],
            [
                'name' => 'PASSPORT',
                'is_expiration_required' => true,
                'is_active' => true,
            ],
            [
                'name' => 'SIM A',
                'is_expiration_required' => true,
                'is_active' => true,
            ],
            [
                'name' => 'SIM C',
                'is_expiration_required' => true,
                'is_active' => true,
            ],
            [
                'name' => 'NPWP',
                'is_expiration_required' => false,
                'is_active' => true,
            ],
            [
                'name' => 'BPJS Kesehatan',
                'is_expiration_required' => false,
                'is_active' => true,
            ],
            [
                'name' => 'BPJS Ketenagakerjaan',
                'is_expiration_required' => false,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            IdentityType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
