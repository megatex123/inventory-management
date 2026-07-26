<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnsiteHandover extends Model
{
    use SoftDeletes;

    protected $table = 'onsite_handovers';
    protected $guarded = ['id'];

    protected $casts = [
        'order_id' => 'integer',
        'round' => 'integer',
        'service_date' => 'date',
        'component_serial_numbers_matched' => 'boolean',
        'customer_order_specification_verified' => 'boolean',
        'required_components_present' => 'boolean',
        'required_tools_present' => 'boolean',
        'required_consumables_present' => 'boolean',
        'workspace_available' => 'boolean',
        'adequate_lighting' => 'boolean',
        'stable_work_surface' => 'boolean',
        'sufficient_working_space' => 'boolean',
        'power_outlet_available' => 'boolean',
        'internet_available' => 'boolean',
        'customer_present_at_arrival' => 'boolean',
        'assembly_area_approved_by_customer' => 'boolean',
        'arrival_photos' => 'array',
        'security_seal_intact' => 'boolean',
        'no_signs_of_transit_damage' => 'boolean',
        'accessories_present' => 'boolean',
        'documentation_present' => 'boolean',
        'transport_case_photos' => 'array',
        'component_packaging_photos' => 'array',
        'cpu_installed' => 'boolean',
        'memory_installed' => 'boolean',
        'storage_installed' => 'boolean',
        'cpu_cooler_installed' => 'boolean',
        'motherboard_installed' => 'boolean',
        'power_supply_installed' => 'boolean',
        'case_fans_installed' => 'boolean',
        'graphics_card_installed' => 'boolean',
        'cable_management_completed' => 'boolean',
        'assembly_photos' => 'array',
        'system_powered_on' => 'boolean',
        'post_successful' => 'boolean',
        'bios_accessible' => 'boolean',
        'cpu_detected' => 'boolean',
        'memory_detected' => 'boolean',
        'storage_detected' => 'boolean',
        'graphics_card_detected' => 'boolean',
        'cpu_cooler_operating' => 'boolean',
        'case_fans_operating' => 'boolean',
        'no_abnormal_noise' => 'boolean',
        'post_build_hardware_photos' => 'array',
        'windows_boot_successful' => 'boolean',
        'windows_activation_verified' => 'boolean',
        'display_output_verified' => 'boolean',
        'network_connected' => 'boolean',
        'internet_accessible' => 'boolean',
        'audio_output_verified' => 'boolean',
        'usb_ports_verified' => 'boolean',
        'rgb_lighting_verified' => 'boolean',
        'post_build_software_photos' => 'array',
        'physical_condition_accepted' => 'boolean',
        'system_boot_verified' => 'boolean',
        'display_verified' => 'boolean',
        'peripherals_verified' => 'boolean',
        'accessories_received' => 'boolean',
        'documentation_received' => 'boolean',
        'customer_demonstration_completed' => 'boolean',
        'customer_acknowledged' => 'boolean',
        'technician_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
