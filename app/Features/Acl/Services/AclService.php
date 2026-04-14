<?php

namespace App\Features\Acl\Services;

use App\Features\Acl\Models\Role;
use App\Features\Acl\Models\Permission;
use Illuminate\Support\Facades\File;

class AclService
{
    protected $actions = ['create', 'read', 'update', 'delete'];

    public function getFeatures()
    {
        $featuresPath = app_path('Features');
        if (!File::exists($featuresPath)) {
            return [];
        }

        $directories = File::directories($featuresPath);
        $features = array_map(function($dir) {
            return basename($dir);
        }, $directories);

        return $features;
    }

    public function getAllPermissions()
    {
        $features = $this->getFeatures();
        $allPermissions = [];

        foreach ($features as $feature) {
            foreach ($this->actions as $action) {
                $allPermissions[] = [
                    'feature' => $feature,
                    'action' => $action,
                    'name' => strtolower($feature) . '.' . $action
                ];
            }
        }

        return $allPermissions;
    }

    public function syncPermissions()
    {
        $permissions = $this->getAllPermissions();
        foreach ($permissions as $p) {
            Permission::firstOrCreate([
                'name' => $p['name']
            ], [
                'feature' => $p['feature'],
                'action' => $p['action']
            ]);
        }

        // Auto-assign all permissions to Administrator role
        $adminRole = Role::where('name', 'Administrator')->first();
        if ($adminRole) {
            $adminRole->permissions()->sync(Permission::pluck('id'));
        }
    }

    public function getRoles()
    {
        return Role::with('permissions')->get();
    }

    public function updateRolePermissions($roleId, array $permissionNames)
    {
        $role = Role::findOrFail($roleId);
        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
        $role->permissions()->sync($permissionIds);
        return $role->load('permissions');
    }
}
