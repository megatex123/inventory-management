<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestCoolingSystemResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_cooling_system_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'cpu_fan_rpm' => 'integer',
        'cpu_pump_rpm' => 'integer',
        'front_fans_rpm' => 'integer',
        'rear_fans_rpm' => 'integer',
        'top_fans_rpm' => 'integer',
        'bottom_fans_rpm' => 'integer',
        'cpu_fan_detected' => 'boolean',
        'cpu_pump_detected' => 'boolean',
        'all_case_fans_detected' => 'boolean',
        'cpu_fan_rpm_stable' => 'boolean',
        'cpu_pump_rpm_stable' => 'boolean',
        'front_fan_rpm_stable' => 'boolean',
        'rear_fan_rpm_stable' => 'boolean',
        'top_fan_rpm_stable' => 'boolean',
        'bottom_fan_rpm_stable' => 'boolean',
        'all_devices_operational' => 'boolean',
        'no_fan_failures' => 'boolean',
        'stable_rpm_monitoring' => 'boolean',
        'cpu_cooler_operation' => 'boolean',
        'pump_operation' => 'boolean',
        'chassis_fan_cooling_operation' => 'boolean',
        'overall_cooling_system' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function performanceTest()
    {
        return $this->belongsTo(PerformanceTest::class, 'performance_test_id');
    }
}
