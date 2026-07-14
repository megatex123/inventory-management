<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlusOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('plus_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plus_order_id');
            $table->unsignedBigInteger('plus_service_id');
            $table->integer('qty')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('plus_order_id')->references('id')->on('plus_orders')->onDelete('cascade');
            $table->foreign('plus_service_id')->references('id')->on('plus_services');
        });
    }

    public function down()
    {
        Schema::dropIfExists('plus_order_items');
    }
}
