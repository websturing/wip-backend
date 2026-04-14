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
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string'
        ]);
        
        $role = Role::create($request->only('name', 'description'));

        if ($request->has('permissions')) {
            $this->service->updateRolePermissions($role->id, $request->permissions);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Role created successfully',
            'data' => $role->load('permissions')
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
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        
        if ($role->name === 'Administrator' && $request->name !== 'Administrator') {
            return response()->json(['message' => 'Cannot rename the Administrator role'], 422);
        }

        $request->validate([
            'name' => 'required|unique:roles,name,' . $id,
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string'
        ]);

        $role->update($request->only('name', 'description'));

        if ($request->has('permissions')) {
            $this->service->updateRolePermissions($id, $request->permissions);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Role updated successfully',
            'data' => $role->load('permissions')
        ]);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'Administrator') {
            return response()->json(['message' => 'Cannot delete the Administrator role'], 422);
        }

        // Dissociate users from this role instead of blocking
        \App\Models\User::where('role_id', $id)->update(['role_id' => null]);

        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully and associated users disassociated'
        ]);
    }

    public function sync()
    {
        $this->service->syncPermissions();
        return response()->json([
            'status' => 'success',
            'message' => 'Permissions synchronized successfully'
        ]);
    }
}
