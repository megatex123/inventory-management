<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundsTable extends Migration
{
    public function up()
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_id', 50)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('plus_order_id')->nullable();
            $table->unsignedBigInteger('merch_order_id')->nullable();
            $table->unsignedBigInteger('thread_order_id')->nullable();
            $table->decimal('refund_amount', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->string('payment_type', 50)->nullable();
            $table->boolean('cash_journal')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('order_id');
            $table->index('plus_order_id');
            $table->index('merch_order_id');
            $table->index('thread_order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('refunds');
    }
}
