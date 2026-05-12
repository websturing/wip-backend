<?php

namespace App\Features\Acl\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Get authorized menus for the current user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Define the Master Menu Structure
        $masterMenus = [
            [
                'id' => 'm1',
                'name' => 'Dashboard',
                'path' => '/admin',
                'icon' => 'solar:home-2-linear',
                'order' => 1
            ],
            [
                'id' => 'm2',
                'name' => 'Master Data',
                'path' => '/admin/master',
                'icon' => 'solar:database-linear',
                'order' => 2,
                'permission' => 'reference.read',
                'children' => [
                    [
                        'id' => 'm2-1',
                        'name' => 'Garment Reference',
                        'path' => '/admin/reference',
                        'icon' => 'solar:reorder-linear',
                        'permission' => 'reference.read',
                    ],
                    [
                        'id' => 'm2-2',
                        'name' => 'Media Library',
                        'path' => '/admin/media',
                        'icon' => 'solar:gallery-linear',
                        'permission' => 'reference.read',
                    ],
                ]
            ],
            [
                'id' => 'm3',
                'name' => 'Production',
                'path' => '/admin/production-group',
                'icon' => 'solar:documents-broken',
                'order' => 3,
                'permission' => 'production.read',
                'children' => [
                    [
                        'id' => 'm3-1',
                        'name' => 'Output',
                        'path' => '/admin/production',
                        'icon' => 'solar:chart-2-linear',
                        'permission' => 'production.read',
                    ],
                    [
                        'id' => 'm3-2',
                        'name' => 'Lines',
                        'path' => '/admin/lines',
                        'icon' => 'solar:tablet-linear',
                        'permission' => 'production.read',
                    ],
                    [
                        'id' => 'm3-2',
                        'name' => 'productivity',
                        'path' => '/admin/productivity',
                        'icon' => 'solar:pie-chart-2-broken',
                        'permission' => 'production.read',
                    ],
                ]
            ],
            [
                'id' => 'm4',
                'name' => 'Industrial Eng.',
                'path' => '/admin/ielayout',
                'icon' => 'solar:layers-linear',
                'order' => 4,
                'permission' => 'ie_layout.read',
                'children' => [
                    [
                        'id' => 'm4-1',
                        'name' => 'Time Study',
                        'path' => '/admin/ielayout',
                        'icon' => 'solar:map-point-linear',
                        'permission' => 'production.read',
                    ],
                    [
                        'id' => 'm4-2',
                        'name' => 'Operation List',
                        'path' => '/admin/operations',
                        'icon' => 'solar:list-linear',
                        'permission' => 'production.read',
                    ],
                ]
            ],
             [
                'id' => 'm4-1',
                'name' => 'Report',
                'path' => '/admin/report',
                'icon' => 'iconoir:git-compare',
                'order' => 5,
                'permission' => 'ie_layout.read',
                'children' => [
                    [
                        'id' => 'm3-1',
                        'name' => 'Completion',
                        'path' => '/admin/report/balance-size',
                        'icon' => 'solar:pie-chart-line-duotone',
                        'permission' => 'production.read',
                    ],
                ]
            ],
            [
                'id' => 'm4-2',
                'name' => 'Packing',
                'path' => '/admin/packing',
                'icon' => 'mynaui:package',
                'order' => 5,
                'permission' => 'packing.read',
                'children' => [
                    [
                        'id' => 'm4-2-1',
                        'name' => 'Output',
                        'path' => '/admin/packing',
                        'icon' => 'boxicons:arrow-in-down-circle-half',
                        'permission' => 'production.read',
                    ],
                ]
            ],
            [
                'id' => 'm5',
                'name' => 'Access Control',
                'path' => '/admin/acl',
                'icon' => 'solar:shield-check-linear',
                'order' => 5,
                'permission' => 'acl.read',
                 'children' => [
                    [
                        'id' => 'm5-1',
                        'name' => 'Roles',
                        'path' => '/admin/acl?tab=roles',
                        'icon' => 'solar:user-speak-rounded-bold-duotone',
                        'permission' => 'acl.read',
                    ],
                    [
                        'id' => 'm5-2',
                        'name' => 'Permissions',
                        'path' => '/admin/acl?tab=permissions',
                        'icon' => 'solar:key-minimalistic-bold-duotone',
                        'permission' => 'acl.read',
                    ],
                ]
            ],
        ];

        // Filter Menus based on permissions
        $filteredMenus = $this->filterMenus($masterMenus, $user);

        return response()->json([
            'status' => 'success',
            'data' => array_values($filteredMenus)
        ]);
    }

    private function filterMenus($menus, $user)
    {
        // If user is Administrator, return all menus
        if ($user->role && $user->role->name === 'Administrator') {
            return $menus;
        }

        // Get user permission names
        $userPermissions = $user->role ? $user->role->permissions->pluck('name')->toArray() : [];

        $filtered = [];
        foreach ($menus as $menu) {
            $hasAccess = true;

            // Check if menu has permission requirement
            if (isset($menu['permission'])) {
                $hasAccess = in_array($menu['permission'], $userPermissions);
            }

            if ($hasAccess) {
                // If has children, filter them too
                if (isset($menu['children']) && !empty($menu['children'])) {
                    $menu['children'] = $this->filterMenus($menu['children'], $user);
                    
                    // If all children were filtered out, hide parent (optional)
                    // if (empty($menu['children'])) continue;
                }
                $filtered[] = $menu;
            }
        }

        return $filtered;
    }
}
