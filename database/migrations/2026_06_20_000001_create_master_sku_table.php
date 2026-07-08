<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateMasterSkuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_sku', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sku_code', 50);
            $table->bigInteger('supplier_id')->nullable();
            // Made nullable later by 2026_07_02_150000_fix_inventory_tables_schema
            $table->unsignedBigInteger('product_raw_id');
            $table->string('product_name', 50)->nullable();
            $table->string('from', 50)->nullable();
            $table->string('cost', 50)->nullable();
            $table->string('unit_type', 50)->nullable();
            $table->bigInteger('lkp_status_sku')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_sku');
    }
}
