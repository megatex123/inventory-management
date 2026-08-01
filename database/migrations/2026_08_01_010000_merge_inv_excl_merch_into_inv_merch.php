<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\MenuItem;

class MergeInvExclMerchIntoInvMerch extends Migration
{
    public function up()
    {
        Schema::table('inv_merch', function (Blueprint $table) {
            $table->boolean('is_exclusive')->default(false)->after('generate_id');
        });

        $lastId = DB::table('inv_merch')
            ->where('inv_merch_id', 'like', 'I-QVMR-%')
            ->orderByDesc('id')
            ->value('inv_merch_id');
        $nextNumber = 1;
        if ($lastId) {
            preg_match('/(\d+)$/', $lastId, $matches);
            $nextNumber = (isset($matches[1]) ? (int) $matches[1] : 0) + 1;
        }

        $exclusiveRows = DB::table('inv_excl_merch')->orderBy('id')->get();
        foreach ($exclusiveRows as $row) {
            DB::table('inv_merch')->insert([
                'inv_merch_id' => 'I-QVMR-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT),
                'sku_code' => $row->sku_code,
                'item_name' => $row->item_name,
                'unit_cost' => $row->unit_cost,
                'max_stock' => $row->max_stock,
                'current_stock' => $row->current_stock,
                'to_restock' => $row->to_restock,
                'status' => $row->status,
                'generate_id' => $row->generate_id,
                'is_exclusive' => true,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);
            $nextNumber++;
        }

        Schema::dropIfExists('inv_excl_merch');

        MenuItem::where('id', 102)->update(['label' => 'All QuiviMerch Inventory']);
        MenuItem::where('id', 101)->update(['label' => 'QuiviMerch Inventory']);

        MenuItem::whereIn('id', [105, 106])->delete();
        MenuItem::where('id', 104)->delete();
    }

    public function down()
    {
        Schema::create('inv_excl_merch', function (Blueprint $table) {
            $table->id();
            $table->string('inv_excl_merch_id', 50);
            $table->string('sku_code', 100);
            $table->string('item_name', 100);
            $table->integer('unit_cost');
            $table->integer('max_stock');
            $table->integer('current_stock');
            $table->integer('to_restock');
            $table->integer('status');
            $table->integer('generate_id');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('inv_merch')->where('is_exclusive', true)->delete();

        Schema::table('inv_merch', function (Blueprint $table) {
            $table->dropColumn('is_exclusive');
        });

        MenuItem::where('id', 101)->update(['label' => 'QM Inventory']);
        MenuItem::where('id', 102)->update(['label' => 'All QM Inventory']);

        $group = MenuItem::create([
            'id' => 104,
            'parent_id' => null,
            'type' => 'header',
            'label' => 'QM Excl. Inventory',
            'icon' => null,
            'route' => null,
            'sort_order' => 1,
            'divider_before' => false,
            'is_active' => true,
        ]);
        MenuItem::create([
            'id' => 105,
            'parent_id' => $group->id,
            'type' => 'link',
            'label' => 'All QM Excl. Inventory',
            'icon' => null,
            'route' => '/inv-excl-merch',
            'sort_order' => 0,
            'divider_before' => false,
            'is_active' => true,
        ]);
        MenuItem::create([
            'id' => 106,
            'parent_id' => $group->id,
            'type' => 'link',
            'label' => 'Add QM Excl. Inventory',
            'icon' => null,
            'route' => '/inv-excl-merch/create',
            'sort_order' => 1,
            'divider_before' => false,
            'is_active' => true,
        ]);
    }
}
