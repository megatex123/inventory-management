<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the "QuiviServe Inventory" sidebar entry (menu_items) under
 * Inventory, alongside QuiviCare/QuiviMerch/Thread Inventory. Was
 * originally inserted directly into this session's local dev DB via
 * tinker when the inv_serve module was built -- never captured in a
 * migration, so it never made it to any other environment. Idempotent:
 * safe to run even where the header row (id=119 locally) already exists,
 * matched by label under the Inventory parent instead of a hardcoded id.
 */
class AddQuiviServeInventoryMenuItem extends Migration
{
    public function up()
    {
        $inventoryGroupId = DB::table('menu_items')
            ->where('type', 'group')
            ->where('label', 'Inventory')
            ->whereNull('parent_id')
            ->value('id');

        if (!$inventoryGroupId) {
            return;
        }

        $headerId = DB::table('menu_items')
            ->where('parent_id', $inventoryGroupId)
            ->where('type', 'header')
            ->where('label', 'QuiviServe Inventory')
            ->value('id');

        if (!$headerId) {
            $headerId = DB::table('menu_items')->insertGetId([
                'parent_id' => $inventoryGroupId,
                'type' => 'header',
                'label' => 'QuiviServe Inventory',
                'sort_order' => 3,
                'divider_before' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $links = [
            ['label' => 'All QuiviServe Inventory', 'route' => '/inv-serve', 'sort_order' => 0],
            ['label' => 'Add QuiviServe Inventory', 'route' => '/inv-serve/create', 'sort_order' => 1],
        ];

        foreach ($links as $link) {
            $exists = DB::table('menu_items')
                ->where('parent_id', $headerId)
                ->where('route', $link['route'])
                ->exists();

            if (!$exists) {
                DB::table('menu_items')->insert([
                    'parent_id' => $headerId,
                    'type' => 'link',
                    'label' => $link['label'],
                    'route' => $link['route'],
                    'sort_order' => $link['sort_order'],
                    'divider_before' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        $headerId = DB::table('menu_items')
            ->where('type', 'header')
            ->where('label', 'QuiviServe Inventory')
            ->value('id');

        if ($headerId) {
            DB::table('menu_items')->where('parent_id', $headerId)->delete();
            DB::table('menu_items')->where('id', $headerId)->delete();
        }
    }
}
