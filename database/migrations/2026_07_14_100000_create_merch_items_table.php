<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchItemsTable extends Migration
{
    public function up()
    {
        Schema::create('merch_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('sku_code', 100);
            $table->string('name', 191);
            $table->decimal('retail_price', 10, 2)->default(0);
            $table->decimal('member_discount_price', 10, 2)->nullable();
            $table->boolean('is_exclusive')->default(false);
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('sku_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('merch_items');
    }
}
