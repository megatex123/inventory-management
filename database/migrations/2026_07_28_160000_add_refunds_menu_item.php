<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\MenuItem;

class AddRefundsMenuItem extends Migration
{
    public function up()
    {
        $group = MenuItem::create([
            'parent_id' => null,
            'type' => 'group',
            'label' => 'Refund',
            'icon' => 'fas fa-fw fa-undo-alt',
            'route' => null,
            'sort_order' => 13,
            'divider_before' => false,
            'is_active' => true,
        ]);

        $header = MenuItem::create([
            'parent_id' => $group->id,
            'type' => 'header',
            'label' => 'Refund Management',
            'icon' => null,
            'route' => null,
            'sort_order' => 0,
            'divider_before' => false,
            'is_active' => true,
        ]);

        MenuItem::create([
            'parent_id' => $header->id,
            'type' => 'link',
            'label' => 'All Refunds',
            'icon' => null,
            'route' => '/refunds',
            'sort_order' => 0,
            'divider_before' => false,
            'is_active' => true,
        ]);

        MenuItem::create([
            'parent_id' => $header->id,
            'type' => 'link',
            'label' => 'Add Refund',
            'icon' => null,
            'route' => '/refunds/create',
            'sort_order' => 1,
            'divider_before' => false,
            'is_active' => true,
        ]);
    }

    public function down()
    {
        MenuItem::where('label', 'Refund')->whereNull('parent_id')->each(function ($group) {
            MenuItem::where('parent_id', $group->id)->each(function ($header) {
                MenuItem::where('parent_id', $header->id)->delete();
                $header->delete();
            });
            $group->delete();
        });
    }
}
