<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateInvExclServeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inv_excl_serve', function (Blueprint $table) {
            $table->id();
            $table->string('inv_excl_serve', 255);
            $table->integer('serve_data_id');
            $table->string('sku_code', 100);
            $table->string('item_name', 100);
            $table->integer('unit_cost');
            $table->integer('max_stock');
            $table->integer('current_stock');
            $table->integer('to_restock');
            $table->integer('status');
            $table->integer('generate_id');
            $table->dateTime('created_at')->nullable()->default(DB::raw('NULL ON UPDATE CURRENT_TIMESTAMP'));
            $table->dateTime('updated_at')->nullable()->default(DB::raw('NULL ON UPDATE CURRENT_TIMESTAMP'));
            // Made nullable later by 2026_07_02_150000_fix_inventory_tables_schema
            $table->dateTime('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inv_excl_serve');
    }
}
