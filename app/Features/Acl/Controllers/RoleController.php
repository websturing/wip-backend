<?php

namespace App\Features\Acl\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Acl\Services\AclService;
use App\Features\Acl\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected $service;

    public function __construct(AclService $service)
    {
        $this->service = $service;
    }

    /**
     * List all roles with their permissions.
     */
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->service->getRoles()
        ]);
    }

    /**
     * Store a new role.
     */
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique:roles,name']);
        
        $role = Role::create($request->only('name', 'description'));

        return response()->json([
            'status' => 'success',
            'message' => 'Role created successfully',
            'data' => $role
        ]);
    }

    /**
     * List all available permissions grouped by feature for frontend checkboxes.
     */
    public function permissions()
    {
        // First, sync feature-based permissions to the database
        $this->service->syncPermissions();

        // Get all permissions and group them by feature
        $permissions = \App\Features\Acl\Models\Permission::all()
            ->groupBy('feature')
            ->map(function($items, $feature) {
                return [
                    'feature' => $feature,
                    'permissions' => $items->map(function($p) {
                        return [
                            'id' => $p->id,
                            'name' => $p->name,
                            'action' => $p->action
                        ];
                    })
                ];
            })->values();

        return response()->json([
            'status' => 'success',
            'data' => $permissions
        ]);
    }

    /**
     * Update permissions for a specific role.
     */
    public function updatePermissions(Request $request, $id)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string' // Array of permission names
        ]);

        $role = $this->service->updateRolePermissions($id, $request->permissions);

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions updated successfully',
            'data' => $role
        ]);
    }
}
