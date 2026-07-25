<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestChecklistItemsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');
            $table->string('section', 30);
            $table->string('item_key', 60);
            $table->string('item_label');
            $table->string('status')->default('pass');
            $table->text('note')->nullable();
            $table->json('photos')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_checklist_items');
    }
}
