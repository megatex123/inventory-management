<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestCpuResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_cpu_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('duration')->nullable();
            $table->string('threads_mode')->nullable();

            $table->decimal('avg_temp_c', 5, 2)->nullable();
            $table->decimal('max_temp_c', 5, 2)->nullable();
            $table->integer('avg_clock_mhz')->nullable();
            $table->decimal('peak_package_power_w', 6, 2)->nullable();
            $table->boolean('thermal_throttling')->nullable();
            $table->boolean('whea_errors')->nullable();
            $table->boolean('system_crash')->nullable();

            $table->boolean('no_thermal_throttling')->nullable();
            $table->boolean('no_whea_errors')->nullable();
            $table->boolean('no_application_crash')->nullable();
            $table->boolean('stable_clock_speed')->nullable();
            $table->boolean('temperature_within_range')->nullable();

            $table->integer('single_core_score')->nullable();
            $table->integer('multi_core_score')->nullable();
            $table->decimal('benchmark_temp_c', 5, 2)->nullable();
            $table->decimal('benchmark_peak_power_w', 6, 2)->nullable();

            $table->boolean('benchmark_completed')->nullable();
            $table->boolean('performance_within_range')->nullable();
            $table->boolean('no_thermal_throttling_benchmark')->nullable();

            $table->decimal('idle_temp_c', 5, 2)->nullable();
            $table->decimal('load_temp_c', 5, 2)->nullable();
            $table->decimal('ccd_temp_c', 5, 2)->nullable();
            $table->decimal('core_voltage_v', 5, 3)->nullable();
            $table->integer('avg_effective_clock_mhz')->nullable();
            $table->decimal('peak_package_power_benchmark_w', 6, 2)->nullable();

            $table->boolean('stability_test_passed')->nullable();
            $table->boolean('benchmark_test_passed')->nullable();
            $table->boolean('thermal_performance_passed')->nullable();
            $table->boolean('clock_stability_passed')->nullable();
            $table->boolean('power_delivery_passed')->nullable();
            $table->boolean('overall_cpu_validation')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_cpu_results');
    }
}
