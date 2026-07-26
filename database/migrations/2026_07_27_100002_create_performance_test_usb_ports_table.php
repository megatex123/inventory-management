<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestUsbPortsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_usb_ports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('location'); // 'front' or 'rear'
            $table->string('label');
            $table->boolean('device_detected')->nullable();
            $table->boolean('data_transfer')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_usb_ports');
    }
}
