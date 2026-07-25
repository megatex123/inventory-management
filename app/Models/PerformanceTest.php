<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTest extends Model
{
    use SoftDeletes;

    protected $table = 'performance_tests';
    protected $guarded = ['id'];

    protected $casts = [
        'order_id' => 'integer',
        'round' => 'integer',
        'overall_cpu_performance' => 'boolean',
        'overall_gpu_performance' => 'boolean',
        'overall_system_stability' => 'boolean',
        'overall_memory_validation' => 'boolean',
        'overall_storage_validation' => 'boolean',
        'overall_cpu_cooling_performance' => 'boolean',
        'overall_cooling_system' => 'boolean',
        'overall_display_output' => 'boolean',
        'overall_network_wireless' => 'boolean',
        'overall_usb_ports' => 'boolean',
        'ready_for_first_boot' => 'boolean',
        'ready_for_bios_configuration' => 'boolean',
        'ready_for_stability_testing' => 'boolean',
        'ready_for_performance_testing' => 'boolean',
        'ready_for_stress_testing' => 'boolean',
        'windows_activation' => 'boolean',
        'windows_update' => 'boolean',
        'driver_chipset' => 'boolean',
        'driver_wifi' => 'boolean',
        'driver_gpu' => 'boolean',
        'driver_bluetooth' => 'boolean',
        'driver_lan' => 'boolean',
        'driver_audio' => 'boolean',
        'os_config_photos' => 'array',
        'drivers_photos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function checklistItems()
    {
        return $this->hasMany(PerformanceTestChecklistItem::class, 'performance_test_id');
    }
}
