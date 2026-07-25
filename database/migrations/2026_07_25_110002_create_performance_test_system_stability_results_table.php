<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestSystemStabilityResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_system_stability_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('duration')->nullable();
            $table->decimal('ambient_temp_c', 5, 2)->nullable();
            $table->string('windows_power_plan')->nullable();

            $table->decimal('max_cpu_temp_c', 5, 2)->nullable();
            $table->decimal('max_gpu_temp_c', 5, 2)->nullable();
            $table->decimal('cpu_package_power_w', 6, 2)->nullable();
            $table->decimal('gpu_power_draw_w', 6, 2)->nullable();
            $table->decimal('total_system_power_w', 6, 2)->nullable();
            $table->string('cpu_clock_stability')->nullable();
            $table->string('gpu_clock_stability')->nullable();

            $table->boolean('unexpected_shutdown')->nullable();
            $table->boolean('bsod')->nullable();
            $table->boolean('application_crash')->nullable();
            $table->boolean('whea_errors')->nullable();
            $table->boolean('thermal_throttling')->nullable();

            $table->boolean('test_completed_successfully')->nullable();
            $table->boolean('no_shutdowns')->nullable();
            $table->boolean('no_bsod')->nullable();
            $table->boolean('no_whea_errors')->nullable();
            $table->boolean('no_thermal_throttling')->nullable();
            $table->boolean('stable_cpu_gpu_operation')->nullable();

            $table->decimal('cpu_temp_c', 5, 2)->nullable();
            $table->decimal('gpu_temp_c', 5, 2)->nullable();
            $table->decimal('motherboard_temp_c', 5, 2)->nullable();
            $table->decimal('vrm_temp_c', 5, 2)->nullable();
            $table->decimal('chipset_temp_c', 5, 2)->nullable();
            $table->integer('cpu_fan_speed_rpm')->nullable();
            $table->integer('pump_speed_rpm')->nullable();

            $table->boolean('combined_load_stability')->nullable();
            $table->boolean('thermal_performance')->nullable();
            $table->boolean('power_delivery')->nullable();
            $table->boolean('cooling_performance')->nullable();
            $table->boolean('overall_system_stability')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id', 'pt_system_stability_results_pt_id_unique');
            $table->foreign('performance_test_id', 'pt_system_stability_results_pt_id_foreign')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_system_stability_results');
    }
}
