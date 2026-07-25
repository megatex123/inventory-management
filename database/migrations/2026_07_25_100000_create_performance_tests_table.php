<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedTinyInteger('round')->default(1);
            $table->string('status')->default('draft');

            $table->string('cooling_solution')->nullable();

            $table->boolean('overall_cpu_performance')->nullable();
            $table->boolean('overall_gpu_performance')->nullable();
            $table->boolean('overall_system_stability')->nullable();
            $table->boolean('overall_memory_validation')->nullable();
            $table->boolean('overall_storage_validation')->nullable();
            $table->boolean('overall_cpu_cooling_performance')->nullable();
            $table->boolean('overall_cooling_system')->nullable();
            $table->boolean('overall_display_output')->nullable();
            $table->boolean('overall_network_wireless')->nullable();
            $table->boolean('overall_usb_ports')->nullable();
            $table->text('overall_notes')->nullable();

            $table->string('thermal_paste_brand')->nullable();
            $table->string('thermal_paste_batch')->nullable();
            $table->string('thermal_paste_application_method')->nullable();

            $table->boolean('ready_for_first_boot')->default(false);
            $table->boolean('ready_for_bios_configuration')->default(false);
            $table->boolean('ready_for_stability_testing')->default(false);
            $table->boolean('ready_for_performance_testing')->default(false);
            $table->boolean('ready_for_stress_testing')->default(false);

            $table->string('os_installed')->nullable();
            $table->boolean('windows_activation')->default(false);
            $table->boolean('windows_update')->default(false);
            $table->text('os_config_note')->nullable();
            $table->json('os_config_photos')->nullable();

            $table->boolean('driver_chipset')->default(false);
            $table->boolean('driver_wifi')->default(false);
            $table->boolean('driver_gpu')->default(false);
            $table->boolean('driver_bluetooth')->default(false);
            $table->boolean('driver_lan')->default(false);
            $table->boolean('driver_audio')->default(false);
            $table->text('drivers_note')->nullable();
            $table->json('drivers_photos')->nullable();

            $table->text('applications_installed')->nullable();
            $table->text('applications_note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_tests');
    }
}
