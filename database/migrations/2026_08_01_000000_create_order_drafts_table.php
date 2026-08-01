<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDraftsTable extends Migration
{
    public function up()
    {
        Schema::create('order_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('draft_id', 50)->unique();
            $table->unsignedInteger('customer_id')->nullable();
            $table->integer('qty')->nullable();
            $table->decimal('sub_total', 12, 2)->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->unsignedInteger('craft_id')->nullable();
            $table->unsignedInteger('serve_id')->nullable();
            $table->unsignedInteger('care_id')->nullable();
            $table->json('order_details_snapshot');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_drafts');
    }
}
