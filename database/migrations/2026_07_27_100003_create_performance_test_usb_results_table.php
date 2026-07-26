<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestUsbResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_usb_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('test_device')->nullable();
            $table->string('usb_device_capacity')->nullable();

            $table->boolean('front_usb_ports_operational')->nullable();
            $table->boolean('rear_usb_ports_operational')->nullable();
            $table->boolean('stable_device_detection')->nullable();
            $table->boolean('successful_data_transfer')->nullable();

            $table->boolean('front_usb_verification')->nullable();
            $table->boolean('rear_usb_verification')->nullable();
            $table->boolean('data_transfer_verification')->nullable();
            $table->boolean('overall_usb_ports')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_usb_results');
    }
}
