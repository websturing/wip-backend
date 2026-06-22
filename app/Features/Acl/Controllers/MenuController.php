<?php

namespace App\Features\Acl\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Features\Acl\Models\Menu;

class MenuController extends Controller
{
    /**
     * Get authorized menus for the current user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $platform = $request->query('platform', 'both');

        // If user has no role, return empty
        if (!$user->role) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        // Base query for menus
        $query = Menu::where('is_active', true)
            ->whereNull('parent_id') // Get only root menus
            ->with(['children' => function($q) use ($platform) {
                $q->where('is_active', true)->orderBy('sort_order');
                if ($platform !== 'both') {
                    $q->whereIn('platform', [$platform, 'both']);
                }
            }])
            ->orderBy('sort_order');

        if ($platform !== 'both') {
            $query->whereIn('platform', [$platform, 'both']);
        }

        // If user is Admin, get all active menus
        if ($user->role->name === 'Admin') {
            $menus = $query->get();
        } else {
            // Otherwise, get only menus assigned to this role
            $roleMenuIds = $user->role->menus()->pluck('menus.id')->toArray();
            
            $menus = $query->whereIn('id', $roleMenuIds)->get();

            // Filter children to only those the role has access to
            $menus->each(function ($menu) use ($roleMenuIds) {
                $menu->setRelation('children', $menu->children->filter(function ($child) use ($roleMenuIds) {
                    return in_array($child->id, $roleMenuIds);
                })->values());
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $menus
        ]);
    }

    /**
     * Get all menus for management (admin view).
     */
    public function all()
    {
        $menus = Menu::with('children')->whereNull('parent_id')->orderBy('sort_order')->get();
        return response()->json(['status' => 'success', 'data' => $menus]);
    }

    /**
     * Create a new menu.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'path' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'sort_order' => 'integer',
            'platform' => 'in:web,mobile,both',
            'is_active' => 'boolean',
        ]);

        $menu = Menu::create($validated);

        return response()->json(['status' => 'success', 'data' => $menu], 201);
    }

    /**
     * Update an existing menu.
     */
    public function update(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'path' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'sort_order' => 'integer',
            'platform' => 'in:web,mobile,both',
            'is_active' => 'boolean',
        ]);

        $menu->update($validated);

        return response()->json(['status' => 'success', 'data' => $menu]);
    }

    /**
     * Delete a menu.
     */
    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete(); // Cascades to children if DB constraint is set

        return response()->json(['status' => 'success', 'message' => 'Menu deleted']);
    }
}
