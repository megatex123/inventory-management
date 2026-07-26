<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestDisplayResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_display_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('connection_type')->nullable();
            $table->string('graphic_driver_version')->nullable();
            $table->string('benchmark_display')->nullable();

            $table->boolean('display_detected')->nullable();
            $table->string('resolution')->nullable();
            $table->unsignedInteger('refresh_rate_hz')->nullable();
            $table->string('hdr_status')->nullable();
            $table->string('output_port_tested')->nullable();

            $table->boolean('display_detected_successfully')->nullable();
            $table->boolean('correct_resolution_applied')->nullable();
            $table->boolean('correct_refresh_rate_applied')->nullable();
            $table->boolean('hdr_functions_correctly')->nullable();
            $table->boolean('stable_video_output')->nullable();

            $table->boolean('display_detection')->nullable();
            $table->boolean('resolution_verification')->nullable();
            $table->boolean('refresh_rate_verification')->nullable();
            $table->boolean('video_output_verification')->nullable();
            $table->boolean('overall_display_output')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_display_results');
    }
}
