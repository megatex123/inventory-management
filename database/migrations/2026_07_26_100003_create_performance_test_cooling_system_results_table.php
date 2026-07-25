<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestCoolingSystemResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_cooling_system_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('cooling_solution')->nullable();
            $table->string('fan_control_mode')->nullable();
            $table->string('fan_curve')->nullable();

            $table->integer('cpu_fan_rpm')->nullable();
            $table->integer('cpu_pump_rpm')->nullable();
            $table->integer('front_fans_rpm')->nullable();
            $table->integer('rear_fans_rpm')->nullable();
            $table->integer('top_fans_rpm')->nullable();
            $table->integer('bottom_fans_rpm')->nullable();

            $table->boolean('cpu_fan_detected')->nullable();
            $table->boolean('cpu_pump_detected')->nullable();
            $table->boolean('all_case_fans_detected')->nullable();
            $table->boolean('cpu_fan_rpm_stable')->nullable();
            $table->boolean('cpu_pump_rpm_stable')->nullable();
            $table->boolean('front_fan_rpm_stable')->nullable();
            $table->boolean('rear_fan_rpm_stable')->nullable();
            $table->boolean('top_fan_rpm_stable')->nullable();
            $table->boolean('bottom_fan_rpm_stable')->nullable();

            $table->boolean('all_devices_operational')->nullable();
            $table->boolean('no_fan_failures')->nullable();
            $table->boolean('stable_rpm_monitoring')->nullable();

            $table->string('front_fan_direction')->nullable();
            $table->string('rear_fan_direction')->nullable();
            $table->string('top_fan_direction')->nullable();
            $table->string('bottom_fan_direction')->nullable();

            $table->boolean('cpu_cooler_operation')->nullable();
            $table->boolean('pump_operation')->nullable();
            $table->boolean('chassis_fan_cooling_operation')->nullable();
            $table->boolean('overall_cooling_system')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id', 'pt_cooling_system_results_pt_id_unique');
            $table->foreign('performance_test_id', 'pt_cooling_system_results_pt_id_foreign')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_cooling_system_results');
    }
}
