<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvMoveTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inv_move', function (Blueprint $table) {
            $table->increments('id');
            $table->string('item_name', 50)->nullable();
            $table->dateTime('date');
            $table->bigInteger('master_sku_id')->nullable();
            $table->bigInteger('destination_id')->nullable();
            $table->bigInteger('reference_id')->nullable();
            $table->string('unit_cost', 50)->nullable();
            $table->bigInteger('quantity')->nullable();
            $table->string('type', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inv_move');
    }
}
