<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestGpuResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_gpu_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('duration')->nullable();
            $table->boolean('vram_test')->nullable();

            $table->decimal('avg_temp_c', 5, 2)->nullable();
            $table->decimal('max_temp_c', 5, 2)->nullable();
            $table->decimal('max_hotspot_temp_c', 5, 2)->nullable();
            $table->integer('avg_clock_mhz')->nullable();
            $table->decimal('peak_power_draw_w', 6, 2)->nullable();
            $table->boolean('thermal_throttling')->nullable();
            $table->boolean('visual_artifacts')->nullable();
            $table->boolean('driver_crash')->nullable();

            $table->boolean('no_visual_artifacts')->nullable();
            $table->boolean('no_driver_crash')->nullable();
            $table->boolean('stable_clock_speed')->nullable();
            $table->boolean('temperature_within_range')->nullable();

            $table->integer('gpu_score')->nullable();
            $table->integer('overall_score')->nullable();
            $table->decimal('benchmark_temp_c', 5, 2)->nullable();
            $table->decimal('benchmark_peak_power_w', 6, 2)->nullable();

            $table->boolean('benchmark_completed')->nullable();
            $table->boolean('performance_within_range')->nullable();
            $table->boolean('no_performance_anomalies')->nullable();

            $table->decimal('idle_temp_c', 5, 2)->nullable();
            $table->decimal('load_temp_c', 5, 2)->nullable();
            $table->decimal('hotspot_temp_c', 5, 2)->nullable();
            $table->integer('core_clock_mhz')->nullable();
            $table->integer('memory_clock_mhz')->nullable();
            $table->decimal('power_draw_w', 6, 2)->nullable();
            $table->integer('fan_speed_rpm')->nullable();

            $table->boolean('stability_test_passed')->nullable();
            $table->boolean('benchmark_test_passed')->nullable();
            $table->boolean('thermal_performance_passed')->nullable();
            $table->boolean('clock_stability_passed')->nullable();
            $table->boolean('cooling_performance_passed')->nullable();
            $table->boolean('overall_gpu_validation')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_gpu_results');
    }
}
