<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThreadBomLinesTable extends Migration
{
    public function up()
    {
        Schema::create('thread_bom_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('thread_bom_header_id');
            $table->string('sku_code', 100);
            $table->string('item_name', 191);
            $table->integer('qty_per_cable')->default(1);
            $table->decimal('unit_cost', 10, 4)->default(0);
            $table->timestamps();

            $table->foreign('thread_bom_header_id')->references('id')->on('thread_bom_headers')->onDelete('cascade');
            $table->index('sku_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('thread_bom_lines');
    }
}
