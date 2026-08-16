<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvServeTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('inv_serve')) {
            return;
        }

        Schema::create('inv_serve', function (Blueprint $table) {
            $table->id();
            $table->string('inv_serve');
            $table->string('sku_code');
            $table->string('item_name');
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->integer('max_stock')->default(0);
            $table->integer('current_stock')->default(0);
            $table->integer('to_restock')->default(0);
            $table->integer('status')->default(1);
            $table->integer('generate_id')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inv_serve');
    }
}
