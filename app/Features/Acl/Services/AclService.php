<?php

namespace App\Features\Acl\Services;

use App\Features\Acl\Models\Role;
use App\Features\Acl\Models\Permission;
use Illuminate\Support\Facades\File;

class AclService
{
    /**
     * MANUAL PERMISSION CATALOG
     * Edit this list to define exactly what permissions are available in the system.
     */
    public function getDefinitions()
    {
        return [
            'Access Control' => [
                'acl.read' => 'View Users & Roles',
                'acl.update' => 'Manage System Permissions',
            ],
            'Production' => [
                'production.read' => 'View Production Dashboard & Feed',
                'production.create' => 'Log Daily Production Output',
                'production.update' => 'Refine/Edit Production Records',
                'production.delete' => 'Remove Erroneous Entries',
            ],
             'packing' => [
                'packing.read' => 'View Production Dashboard & Feed',
                'packing.create' => 'Log Daily Production Output',
                'packing.update' => 'Refine/Edit Production Records',
                'packing.delete' => 'Remove Erroneous Entries',
            ],
            'Master Data' => [
                'reference.read' => 'View Master References (Lots, Buyers)',
                'reference.create' => 'Import/Add New Garment References',
                'reference.update' => 'Modify Existing Reference Data',
                'reference.delete' => 'Delete Standard References',
            ],
            'Industrial Engineering' => [
                'ie_layout.read' => 'View Layouts & Manpower Calculations',
                'ie_layout.create' => 'Initialize New IE Layouts',
                'ie_layout.update' => 'Adjust Cycle Times & Line Balancing',
                'ie_layout.delete' => 'Remove IE Layout Records',
            ],
            'WIP & Packing' => [
                'wip.read' => 'Track Real-time WIP Levels',
                'wip.create' => 'Log Packing & Shipment Progress',
                'wip.update' => 'Modify WIP Adjustments',
                'wip.delete' => 'Clear WIP Records',
            ],
            // [AUTO_GEN_MARKER]
        ];
    }

    public function syncPermissions()
    {
        $definitions = $this->getDefinitions();
        $validNames = [];

        foreach ($definitions as $group => $perms) {
            foreach ($perms as $name => $label) {
                $validNames[] = $name;
                
                // Get technical action (last part of name)
                $action = last(explode('.', $name));

                Permission::updateOrCreate([
                    'name' => $name
                ], [
                    'label' => $label,
                    'feature' => $group,
                    'action' => $action
                ]);
            }
        }

        // Optional: Remove permissions no longer in catalog
        Permission::whereNotIn('name', $validNames)->delete();

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
