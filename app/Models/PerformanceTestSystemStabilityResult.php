<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestSystemStabilityResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_system_stability_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'ambient_temp_c' => 'float',
        'max_cpu_temp_c' => 'float',
        'max_gpu_temp_c' => 'float',
        'cpu_package_power_w' => 'float',
        'gpu_power_draw_w' => 'float',
        'total_system_power_w' => 'float',
        'unexpected_shutdown' => 'boolean',
        'bsod' => 'boolean',
        'application_crash' => 'boolean',
        'whea_errors' => 'boolean',
        'thermal_throttling' => 'boolean',
        'test_completed_successfully' => 'boolean',
        'no_shutdowns' => 'boolean',
        'no_bsod' => 'boolean',
        'no_whea_errors' => 'boolean',
        'no_thermal_throttling' => 'boolean',
        'stable_cpu_gpu_operation' => 'boolean',
        'cpu_temp_c' => 'float',
        'gpu_temp_c' => 'float',
        'motherboard_temp_c' => 'float',
        'vrm_temp_c' => 'float',
        'chipset_temp_c' => 'float',
        'cpu_fan_speed_rpm' => 'integer',
        'pump_speed_rpm' => 'integer',
        'combined_load_stability' => 'boolean',
        'thermal_performance' => 'boolean',
        'power_delivery' => 'boolean',
        'cooling_performance' => 'boolean',
        'overall_system_stability' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function performanceTest()
    {
        return $this->belongsTo(PerformanceTest::class, 'performance_test_id');
    }
}
