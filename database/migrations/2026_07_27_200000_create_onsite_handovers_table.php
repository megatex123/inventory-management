<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnsiteHandoversTable extends Migration
{
    public function up()
    {
        Schema::create('onsite_handovers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('round')->default(1);
            $table->string('report_id')->unique();
            $table->string('report_version')->nullable();
            $table->string('status')->default('in_progress');

            // Report Information
            $table->date('service_date')->nullable();
            $table->time('arrival_time')->nullable();
            $table->time('work_start_time')->nullable();
            $table->time('work_completion_time')->nullable();
            $table->string('technician_name')->nullable();
            $table->string('assistant_technician')->nullable();
            $table->string('service_location')->nullable();
            $table->string('service_type')->nullable();

            // Customer Information
            $table->string('customer_present_during_assembly')->nullable();
            $table->string('authorised_representative')->nullable();
            $table->string('service_address')->nullable();

            // Build Information
            $table->string('pc_purpose')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('operating_system_version')->nullable();

            // Studio Documentation Verification
            $table->boolean('component_serial_numbers_matched')->nullable();
            $table->boolean('customer_order_specification_verified')->nullable();
            $table->boolean('required_components_present')->nullable();
            $table->boolean('required_tools_present')->nullable();
            $table->boolean('required_consumables_present')->nullable();
            $table->text('studio_docs_notes')->nullable();

            // On-Site Arrival Verification
            $table->string('service_environment')->nullable();
            $table->boolean('workspace_available')->nullable();
            $table->boolean('adequate_lighting')->nullable();
            $table->boolean('stable_work_surface')->nullable();
            $table->boolean('sufficient_working_space')->nullable();
            $table->boolean('power_outlet_available')->nullable();
            $table->boolean('internet_available')->nullable();
            $table->boolean('customer_present_at_arrival')->nullable();
            $table->boolean('assembly_area_approved_by_customer')->nullable();
            $table->json('arrival_photos')->nullable();
            $table->text('arrival_notes')->nullable();

            // Transportation Inspection
            $table->string('transport_case_note')->nullable();
            $table->string('transport_case_status')->nullable();
            $table->json('transport_case_photos')->nullable();
            $table->string('component_packaging_note')->nullable();
            $table->string('component_packaging_status')->nullable();
            $table->json('component_packaging_photos')->nullable();
            $table->boolean('security_seal_intact')->nullable();
            $table->boolean('no_signs_of_transit_damage')->nullable();
            $table->boolean('accessories_present')->nullable();
            $table->boolean('documentation_present')->nullable();
            $table->text('transportation_notes')->nullable();
            $table->string('transportation_verdict')->nullable();

            // QuiviCraft Assembly
            $table->boolean('cpu_installed')->nullable();
            $table->boolean('memory_installed')->nullable();
            $table->boolean('storage_installed')->nullable();
            $table->boolean('cpu_cooler_installed')->nullable();
            $table->boolean('motherboard_installed')->nullable();
            $table->boolean('power_supply_installed')->nullable();
            $table->boolean('case_fans_installed')->nullable();
            $table->boolean('graphics_card_installed')->nullable();
            $table->boolean('cable_management_completed')->nullable();
            $table->json('assembly_photos')->nullable();
            $table->text('assembly_notes')->nullable();

            // Post-Build Hardware Verification
            $table->boolean('system_powered_on')->nullable();
            $table->boolean('post_successful')->nullable();
            $table->boolean('bios_accessible')->nullable();
            $table->boolean('cpu_detected')->nullable();
            $table->boolean('memory_detected')->nullable();
            $table->boolean('storage_detected')->nullable();
            $table->boolean('graphics_card_detected')->nullable();
            $table->boolean('cpu_cooler_operating')->nullable();
            $table->boolean('case_fans_operating')->nullable();
            $table->boolean('no_abnormal_noise')->nullable();
            $table->json('post_build_hardware_photos')->nullable();
            $table->text('post_build_hardware_notes')->nullable();

            // Post-Build Software Verification
            $table->boolean('windows_boot_successful')->nullable();
            $table->boolean('windows_activation_verified')->nullable();
            $table->boolean('display_output_verified')->nullable();
            $table->boolean('network_connected')->nullable();
            $table->boolean('internet_accessible')->nullable();
            $table->boolean('audio_output_verified')->nullable();
            $table->boolean('usb_ports_verified')->nullable();
            $table->boolean('rgb_lighting_verified')->nullable();
            $table->json('post_build_software_photos')->nullable();
            $table->text('post_build_software_notes')->nullable();

            // Customer Acceptance
            $table->boolean('physical_condition_accepted')->nullable();
            $table->boolean('system_boot_verified')->nullable();
            $table->boolean('display_verified')->nullable();
            $table->boolean('peripherals_verified')->nullable();
            $table->boolean('accessories_received')->nullable();
            $table->boolean('documentation_received')->nullable();
            $table->boolean('customer_demonstration_completed')->nullable();
            $table->text('customer_acceptance_notes')->nullable();

            // Acknowledgement
            $table->string('customer_ack_name')->nullable();
            $table->boolean('customer_acknowledged')->nullable();
            $table->string('technician_ack_name')->nullable();
            $table->boolean('technician_acknowledged')->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['order_id', 'round']);
            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('onsite_handovers');
    }
}
