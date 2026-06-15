<?php

namespace App\Features\Acl\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Acl\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Store a newly created custom permission.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'feature' => 'required|string|max:255',
            'action' => 'required|string|in:read,write,update,delete',
            'label' => 'nullable|string|max:255',
        ]);

        $permission = Permission::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission created successfully',
            'data' => $permission
        ], 201);
    }

    /**
     * Update an existing permission.
     */
    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $id,
            'feature' => 'required|string|max:255',
            'action' => 'required|string|in:read,write,update,delete',
            'label' => 'nullable|string|max:255',
        ]);

        $permission->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission updated successfully',
            'data' => $permission
        ]);
    }

    /**
     * Delete a permission.
     */
    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Permission deleted successfully'
        ]);
    }
}
