<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCraftInspectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('craft_inspections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedTinyInteger('phase')->default(2);
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
        });

        Schema::create('craft_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('craft_inspection_id');
            $table->string('component_type', 20);
            $table->unsignedBigInteger('order_detail_id')->nullable();
            $table->json('fields')->nullable();

            $table->boolean('model_verified')->default(false);
            $table->boolean('serial_recorded')->default(false);
            $table->boolean('factory_seal')->default(false);
            $table->boolean('qc_pass')->default(false);

            $table->string('inspection_status')->nullable();
            $table->text('inspection_note')->nullable();
            $table->json('inspection_photos')->nullable();

            $table->string('packaging_status')->nullable();
            $table->text('packaging_note')->nullable();
            $table->json('packaging_photos')->nullable();

            $table->string('condition_status')->nullable();
            $table->text('condition_note')->nullable();
            $table->json('condition_photos')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('craft_inspection_id')->references('id')->on('craft_inspections')->onDelete('cascade');
            $table->foreign('order_detail_id')->references('id')->on('order_details')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('craft_inspection_items');
        Schema::dropIfExists('craft_inspections');
    }
}
