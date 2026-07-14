<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvMerchTable extends Migration
{
    public function up()
    {
        Schema::create('inv_merch', function (Blueprint $table) {
            $table->id();
            $table->string('inv_merch_id', 50)->unique();
            $table->string('sku_code', 100);
            $table->string('item_name', 100);
            $table->integer('unit_cost')->default(0);
            $table->integer('max_stock')->default(0);
            $table->integer('current_stock')->default(0);
            $table->integer('to_restock')->default(0);
            $table->integer('status')->default(1);
            $table->integer('generate_id')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('sku_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inv_merch');
    }
}
