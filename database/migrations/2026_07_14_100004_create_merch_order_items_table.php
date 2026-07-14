<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('merch_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merch_order_id');
            $table->unsignedBigInteger('merch_item_id');
            $table->integer('qty')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->boolean('discount_applied')->default(false);
            $table->decimal('line_total', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('merch_order_id')->references('id')->on('merch_orders')->onDelete('cascade');
            $table->foreign('merch_item_id')->references('id')->on('merch_items');
        });
    }

    public function down()
    {
        Schema::dropIfExists('merch_order_items');
    }
}
