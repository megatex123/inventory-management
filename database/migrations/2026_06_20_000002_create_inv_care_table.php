<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateInvCareTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inv_care', function (Blueprint $table) {
            $table->id();
            $table->string('inv_care', 255);
            $table->integer('care_id');
            $table->string('sku_code', 100);
            $table->string('item_name', 100);
            $table->integer('unit_cost');
            $table->integer('max_stock');
            $table->integer('current_stock');
            $table->integer('category');
            $table->integer('status');
            $table->integer('generate_id');
            $table->integer('serial_label');
            $table->dateTime('warranty_starts');
            $table->integer('warranty_duration');
            $table->dateTime('warranty_ends');
            $table->string('manufacturer', 100);
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
        Schema::dropIfExists('inv_care');
    }
}
