<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('merch_orders', function (Blueprint $table) {
            $table->id();
            $table->string('merch_order_id', 50)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('merch_orders');
    }
}
