<?php

namespace App\Features\Acl\Seeders;

use Illuminate\Database\Seeder;
use App\Features\Acl\Models\Role;
use App\Features\Acl\Models\Permission;
use App\Features\Acl\Services\AclService;
use App\Models\User;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $aclService = new AclService();
        
        // 1. Sync all permissions from feature folders
        $aclService->syncPermissions();
        echo "✅ Permissions Synced!\n";

        // 2. Create Admin Role
        $adminRole = Role::firstOrCreate(['name' => 'Admin'], [
            'description' => 'Super Administrator with all permissions'
        ]);
        echo "✅ Admin Role Created!\n";

        // 3. Assign All Permissions to Admin Role
        $allPermissions = Permission::all()->pluck('id');
        $adminRole->permissions()->sync($allPermissions);
        echo "✅ All Permissions assigned to Admin!\n";

        // 4. Link Admin Role to Existing Admin User
        $user = User::where('email', 'admin@test.com')->first();
        if ($user) {
            $user->update(['role_id' => $adminRole->id]);
            echo "✅ User '{$user->email}' linked to Admin Role!\n";
        } else {
            echo "❌ User 'admin@test.com' not found! Please run AuthSeeder first.\n";
        }
    }
}
