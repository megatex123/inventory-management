<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestCoolingPerformanceResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_cooling_performance_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('cooling_solution')->nullable();
            $table->string('duration')->nullable();
            $table->decimal('ambient_temp_c', 5, 2)->nullable();

            $table->decimal('cpu_idle_temp_c', 5, 2)->nullable();
            $table->decimal('cpu_load_temp_c', 5, 2)->nullable();
            $table->decimal('gpu_idle_temp_c', 5, 2)->nullable();
            $table->decimal('gpu_load_temp_c', 5, 2)->nullable();
            $table->decimal('vrm_idle_temp_c', 5, 2)->nullable();
            $table->decimal('vrm_load_temp_c', 5, 2)->nullable();
            $table->decimal('chipset_idle_temp_c', 5, 2)->nullable();
            $table->decimal('chipset_load_temp_c', 5, 2)->nullable();

            $table->boolean('cpu_temp_within_range')->nullable();
            $table->boolean('gpu_temp_within_range')->nullable();
            $table->boolean('vrm_temp_within_range')->nullable();
            $table->boolean('chipset_temp_within_range')->nullable();

            $table->boolean('cooling_operating_normally')->nullable();
            $table->boolean('no_thermal_throttling')->nullable();
            $table->boolean('temps_stable_under_load')->nullable();

            $table->boolean('cpu_cooling_performance')->nullable();
            $table->boolean('gpu_cooling_performance')->nullable();
            $table->boolean('motherboard_cooling_performance')->nullable();
            $table->boolean('overall_cpu_cooling_performance')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id', 'pt_cooling_performance_results_pt_id_unique');
            $table->foreign('performance_test_id', 'pt_cooling_performance_results_pt_id_foreign')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_cooling_performance_results');
    }
}
