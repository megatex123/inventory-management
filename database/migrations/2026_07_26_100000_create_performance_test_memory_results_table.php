<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestMemoryResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_memory_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('duration')->nullable();
            $table->string('memory_capacity')->nullable();
            $table->string('memory_configuration')->nullable();
            $table->string('expo_xmp_profile')->nullable();
            $table->integer('memory_frequency_mts')->nullable();
            $table->string('memory_timings')->nullable();
            $table->string('memory_passes')->nullable();

            $table->integer('total_passes_completed')->nullable();
            $table->integer('total_tests_completed')->nullable();
            $table->integer('memory_errors_detected')->nullable();

            $table->boolean('test_completed_successfully')->nullable();
            $table->boolean('zero_memory_errors')->nullable();
            $table->boolean('stable_expo_xmp_operation')->nullable();

            $table->string('capacity_expected')->nullable();
            $table->string('capacity_detected')->nullable();
            $table->boolean('capacity_status')->nullable();
            $table->string('configuration_expected')->nullable();
            $table->string('configuration_detected')->nullable();
            $table->boolean('configuration_status')->nullable();
            $table->string('frequency_expected')->nullable();
            $table->string('frequency_detected')->nullable();
            $table->boolean('frequency_status')->nullable();
            $table->string('expo_xmp_expected')->nullable();
            $table->string('expo_xmp_detected')->nullable();
            $table->boolean('expo_xmp_status')->nullable();

            $table->boolean('memory_stability_test')->nullable();
            $table->boolean('memory_frequency_verified')->nullable();
            $table->boolean('error_detection')->nullable();
            $table->boolean('overall_memory_validation')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_memory_results');
    }
}
