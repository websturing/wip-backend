<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default User
        $this->call(\App\Features\Auth\Seeders\AuthSeeder::class);

        // 2. Setup ACL (Roles & Permissions)
        $this->call(\App\Features\Acl\Seeders\RoleSeeder::class);

        // 3. Setup Production Lines
        $this->call(\Database\Seeders\LineSeeder::class);

        // 4. Setup IE Layout Operations
        $this->call(\App\Features\IeLayout\Seeders\OperationSeeder::class);
        
        // 5. Setup Master Sizes
        $this->call(\Database\Seeders\SizeSeeder::class);

        // 6. Setup Color, Fabric & Lot reference data
        $this->call(\Database\Seeders\ColorFabricSeeder::class);

        // 7. Setup Laying Planning sample data
        $this->call(\App\Features\LayingPlanning\Seeders\LayingPlanningSeeder::class);
    }
}
