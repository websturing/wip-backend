<?php

namespace App\Features\Acl\Seeders;

use Illuminate\Database\Seeder;
use App\Features\Acl\Models\Menu;
use App\Features\Acl\Models\Role;

class MenuSeeder extends Seeder
{
    public function run()
    {
        // 1. Dashboard
        $m1 = Menu::firstOrCreate(['name' => 'Dashboard'], [
            'path' => '/admin', 'icon' => 'solar:home-2-linear', 'sort_order' => 1
        ]);

        // 2. Master Data
        $m2 = Menu::firstOrCreate(['name' => 'Master Data'], [
            'path' => '/admin/master', 'icon' => 'solar:database-linear', 'sort_order' => 2
        ]);
        Menu::firstOrCreate(['name' => 'Garment Reference'], [
            'path' => '/admin/reference', 'icon' => 'solar:reorder-linear', 'parent_id' => $m2->id, 'sort_order' => 1
        ]);
        Menu::firstOrCreate(['name' => 'Media Library'], [
            'path' => '/admin/media', 'icon' => 'solar:gallery-linear', 'parent_id' => $m2->id, 'sort_order' => 2
        ]);

        // 3. Production
        $m3 = Menu::firstOrCreate(['name' => 'Production'], [
            'path' => '/admin/production-group', 'icon' => 'solar:documents-broken', 'sort_order' => 3
        ]);
        Menu::firstOrCreate(['name' => 'Output'], ['path' => '/admin/production', 'icon' => 'solar:chart-2-linear', 'parent_id' => $m3->id, 'sort_order' => 1]);
        Menu::firstOrCreate(['name' => 'Lines'], ['path' => '/admin/lines', 'icon' => 'solar:tablet-linear', 'parent_id' => $m3->id, 'sort_order' => 2]);
        Menu::firstOrCreate(['name' => 'productivity'], ['path' => '/admin/productivity', 'icon' => 'solar:pie-chart-2-broken', 'parent_id' => $m3->id, 'sort_order' => 3]);

        // 4. Industrial Eng.
        $m4 = Menu::firstOrCreate(['name' => 'Industrial Eng.'], [
            'path' => '/admin/ielayout', 'icon' => 'solar:layers-linear', 'sort_order' => 4
        ]);
        $m4_1 = Menu::firstOrCreate(['name' => 'Sewing Report'], [
            'path' => '/admin/report', 'icon' => 'iconoir:git-compare', 'parent_id' => $m4->id, 'sort_order' => 1
        ]);
        Menu::firstOrCreate(['name' => 'Completion'], ['path' => '/admin/report/balance-size', 'icon' => 'solar:pie-chart-line-duotone', 'parent_id' => $m4_1->id, 'sort_order' => 1]);
        Menu::firstOrCreate(['name' => 'Productivity'], ['path' => '/admin/report/productivity', 'icon' => 'solar:pie-chart-2-broken', 'parent_id' => $m4_1->id, 'sort_order' => 2]);
        Menu::firstOrCreate(['name' => 'Summary Statistic'], ['path' => '/admin/detailed-statistics', 'icon' => 'solar:chart-square-bold-duotone', 'parent_id' => $m4_1->id, 'sort_order' => 3]);
        Menu::firstOrCreate(['name' => 'Output Sewing Report'], ['path' => '/admin/report/output-sewing', 'icon' => 'solar:file-download-bold-duotone', 'parent_id' => $m4_1->id, 'sort_order' => 4]);

        // 5. Packing
        $m5 = Menu::firstOrCreate(['name' => 'Packing'], [
            'path' => '/admin/packing', 'icon' => 'mynaui:package', 'sort_order' => 5
        ]);
        Menu::firstOrCreate(['name' => 'Output'], ['path' => '/admin/packing', 'icon' => 'boxicons:arrow-in-down-circle-half', 'parent_id' => $m5->id, 'sort_order' => 1]);

        // 6. Access Control
        $m6 = Menu::firstOrCreate(['name' => 'Access Control'], [
            'path' => '/admin/acl', 'icon' => 'solar:shield-check-linear', 'sort_order' => 6
        ]);
        Menu::firstOrCreate(['name' => 'Roles'], ['path' => '/admin/acl?tab=roles', 'icon' => 'solar:user-speak-rounded-bold-duotone', 'parent_id' => $m6->id, 'sort_order' => 1]);
        Menu::firstOrCreate(['name' => 'Permissions'], ['path' => '/admin/acl?tab=permissions', 'icon' => 'solar:key-minimalistic-bold-duotone', 'parent_id' => $m6->id, 'sort_order' => 2]);

        // Assign all to Administrator
        $admin = Role::where('name', 'Administrator')->first();
        if ($admin) {
            $admin->menus()->sync(Menu::pluck('id')->toArray());
        }
    }
}
