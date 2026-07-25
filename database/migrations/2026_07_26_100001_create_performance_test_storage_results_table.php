<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestStorageResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_storage_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->string('duration')->nullable();
            $table->string('storage_device')->nullable();
            $table->string('interface')->nullable();
            $table->string('capacity')->nullable();
            $table->string('firmware_version')->nullable();

            $table->string('health_status')->nullable();
            $table->decimal('drive_temp_c', 5, 2)->nullable();
            $table->string('power_on_hours')->nullable();
            $table->string('interface_mode')->nullable();

            $table->boolean('health_status_good')->nullable();
            $table->boolean('drive_detected_correctly')->nullable();
            $table->boolean('firmware_verified')->nullable();
            $table->boolean('temperature_within_range')->nullable();

            $table->decimal('sequential_read_speed_mbs', 8, 2)->nullable();
            $table->decimal('sequential_write_speed_mbs', 8, 2)->nullable();

            $table->boolean('benchmark_completed')->nullable();
            $table->boolean('read_performance_within_range')->nullable();
            $table->boolean('write_performance_within_range')->nullable();

            $table->string('driver_expected')->nullable();
            $table->string('driver_detected')->nullable();
            $table->boolean('driver_status')->nullable();

            $table->boolean('storage_health_verification')->nullable();
            $table->boolean('firmware_verification')->nullable();
            $table->boolean('performance_verification')->nullable();
            $table->boolean('temperature_verification')->nullable();
            $table->boolean('overall_storage_validation')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_storage_results');
    }
}
