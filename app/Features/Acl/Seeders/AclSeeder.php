<?php

namespace App\Features\Acl\Seeders;

use Illuminate\Database\Seeder;
use App\Features\Acl\Models\Role;
use App\Features\Acl\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Features\Acl\Services\AclService;

class AclSeeder extends Seeder
{
    public function run(AclService $service): void
    {
        // 1. Sync all permissions from Features folder
        $service->syncPermissions();

        // 2. Create Roles
        $adminRole = Role::updateOrCreate(['name' => 'Administrator'], [
            'description' => 'System superuser with unrestricted access to all modules and configurations.'
        ]);
        
        $operatorRole = Role::updateOrCreate(['name' => 'Operator'], [
            'description' => 'Standard production staff capable of logging output and managing daily operations.'
        ]);
        
        // 3. Assign all permissions to Admin
        $allPermissionIds = Permission::pluck('id');
        $adminRole->permissions()->sync($allPermissionIds);

        // 4. Assign limited permissions to Operator
        $operatorPermissions = Permission::whereIn('action', ['read', 'create'])
            ->whereIn('feature', ['Production', 'Productivity'])
            ->pluck('id');
        $operatorRole->permissions()->sync($operatorPermissions);

        // 5. Create Default Administrator User
        $user = User::updateOrCreate(
            ['email' => 'admin@gla.id'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
            ]
        );
    }
}
