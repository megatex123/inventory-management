<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnsiteHandoversStudioTable extends Migration
{
    public function up()
    {
        Schema::create('onsite_handovers_studio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('round')->default(1);
            $table->string('report_id')->unique();
            $table->string('report_version')->nullable();
            $table->string('status')->default('in_progress');

            // Report Information
            $table->date('service_date')->nullable();
            $table->time('arrival_time')->nullable();
            $table->time('handover_completion_time')->nullable();
            $table->string('technician_name')->nullable();
            $table->string('assistant_technician')->nullable();
            $table->string('service_location')->nullable();
            $table->string('service_type')->nullable();

            // Build Information
            $table->string('operating_system')->nullable();
            $table->string('operating_system_version')->nullable();

            // Studio Documentation Verification
            $table->boolean('security_seal_verified_before_delivery')->nullable();
            $table->text('studio_docs_notes')->nullable();

            // On-Site Arrival Verification
            $table->boolean('workspace_available')->nullable();
            $table->boolean('power_outlet_available')->nullable();
            $table->boolean('display_available')->nullable();
            $table->boolean('keyboard_available')->nullable();
            $table->boolean('mouse_available')->nullable();
            $table->boolean('internet_available')->nullable();
            $table->json('arrival_photos')->nullable();
            $table->text('arrival_notes')->nullable();

            // Post-Transport Hardware Verification
            $table->boolean('gpu_securely_installed')->nullable();
            $table->boolean('memory_fully_seated')->nullable();
            $table->boolean('cpu_cooler_secure')->nullable();
            $table->boolean('power_connections_secure')->nullable();
            $table->boolean('storage_secure')->nullable();
            $table->boolean('no_loose_cables')->nullable();
            $table->boolean('no_loose_screws')->nullable();
            $table->json('post_transport_photos')->nullable();
            $table->text('post_transport_notes')->nullable();

            // Post-Handover System Verification
            $table->boolean('system_powered_on')->nullable();
            $table->boolean('post_successful')->nullable();
            $table->boolean('windows_boot_successful')->nullable();
            $table->boolean('display_output_verified')->nullable();
            $table->boolean('network_connected')->nullable();
            $table->boolean('internet_accessible')->nullable();
            $table->boolean('audio_verified')->nullable();
            $table->boolean('usb_ports_verified')->nullable();
            $table->boolean('rgb_lighting_verified')->nullable();
            $table->json('post_handover_photos')->nullable();
            $table->text('post_handover_notes')->nullable();

            // Customer Acceptance
            $table->boolean('physical_condition_accepted')->nullable();
            $table->boolean('system_boot_verified')->nullable();
            $table->boolean('display_verified')->nullable();
            $table->boolean('accessories_received')->nullable();
            $table->boolean('documentation_received')->nullable();
            $table->boolean('customer_demonstration_completed')->nullable();
            $table->boolean('customer_questions_addressed')->nullable();
            $table->text('customer_acceptance_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['order_id', 'round']);
            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('onsite_handovers_studio');
    }
}
