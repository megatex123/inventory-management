<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnsiteHandoverStudio extends Model
{
    use SoftDeletes;

    protected $table = 'onsite_handovers_studio';
    protected $guarded = ['id'];

    protected $casts = [
        'order_id' => 'integer',
        'round' => 'integer',
        'service_date' => 'date',
        'security_seal_verified_before_delivery' => 'boolean',
        'workspace_available' => 'boolean',
        'power_outlet_available' => 'boolean',
        'display_available' => 'boolean',
        'keyboard_available' => 'boolean',
        'mouse_available' => 'boolean',
        'internet_available' => 'boolean',
        'arrival_photos' => 'array',
        'gpu_securely_installed' => 'boolean',
        'memory_fully_seated' => 'boolean',
        'cpu_cooler_secure' => 'boolean',
        'power_connections_secure' => 'boolean',
        'storage_secure' => 'boolean',
        'no_loose_cables' => 'boolean',
        'no_loose_screws' => 'boolean',
        'post_transport_photos' => 'array',
        'system_powered_on' => 'boolean',
        'post_successful' => 'boolean',
        'windows_boot_successful' => 'boolean',
        'display_output_verified' => 'boolean',
        'network_connected' => 'boolean',
        'internet_accessible' => 'boolean',
        'audio_verified' => 'boolean',
        'usb_ports_verified' => 'boolean',
        'rgb_lighting_verified' => 'boolean',
        'post_handover_photos' => 'array',
        'physical_condition_accepted' => 'boolean',
        'system_boot_verified' => 'boolean',
        'display_verified' => 'boolean',
        'accessories_received' => 'boolean',
        'documentation_received' => 'boolean',
        'customer_demonstration_completed' => 'boolean',
        'customer_questions_addressed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
