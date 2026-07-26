<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTestNetworkResultsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_test_network_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('performance_test_id');

            $table->boolean('wired_network')->nullable();
            $table->string('wireless_network')->nullable();
            $table->string('internet_access_available')->nullable();
            $table->string('bluetooth_device_tested')->nullable();

            $table->boolean('lan_detected')->nullable();
            $table->boolean('lan_connected')->nullable();
            $table->boolean('wifi_adapter_detected')->nullable();
            $table->boolean('wifi_connected')->nullable();
            $table->boolean('internet_access')->nullable();
            $table->boolean('bluetooth_adapter_detected')->nullable();
            $table->boolean('bluetooth_pairing_successful')->nullable();

            $table->boolean('lan_operating_normally')->nullable();
            $table->boolean('wifi_operating_normally')->nullable();
            $table->boolean('internet_connection_verified')->nullable();
            $table->boolean('bluetooth_pairing_confirmed')->nullable();
            $table->boolean('wifi_antenna_installed_correctly')->nullable();

            $table->boolean('lan_verification')->nullable();
            $table->boolean('wifi_verification')->nullable();
            $table->boolean('internet_connectivity')->nullable();
            $table->boolean('bluetooth_verification')->nullable();
            $table->boolean('overall_network_wireless')->nullable();

            $table->text('technician_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('performance_test_id');
            $table->foreign('performance_test_id')->references('id')->on('performance_tests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_test_network_results');
    }
}
