<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThreadOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('thread_orders', function (Blueprint $table) {
            $table->id();
            $table->string('thread_order_id', 50)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id')->nullable();
            // pending -> cutting -> sleeving -> qc -> complete, per the
            // QuiviTech Overview V2 spec's QuiviThread status workflow.
            $table->string('status', 20)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('warranty_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('thread_orders');
    }
}
