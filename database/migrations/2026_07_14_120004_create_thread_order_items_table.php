<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThreadOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('thread_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('thread_order_id');
            $table->string('cable_type', 30);
            $table->string('psu_brand', 100);
            $table->string('colour_variant', 50)->nullable();
            $table->integer('qty')->default(1);
            $table->decimal('wire_length_cm', 8, 2)->nullable();
            $table->decimal('sleeve_length_cm', 8, 2)->nullable();
            // Snapshot of the resolved BOM lines + cost at order time, so
            // later edits to thread_bom_lines don't retroactively change
            // historical orders — same pattern as CraftInspectionItem.fields.
            $table->json('resolved_components')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('thread_order_id')->references('id')->on('thread_orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('thread_order_items');
    }
}
