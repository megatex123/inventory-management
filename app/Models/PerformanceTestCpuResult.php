<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestCpuResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_cpu_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'avg_temp_c' => 'float',
        'max_temp_c' => 'float',
        'avg_clock_mhz' => 'integer',
        'peak_package_power_w' => 'float',
        'thermal_throttling' => 'boolean',
        'whea_errors' => 'boolean',
        'system_crash' => 'boolean',
        'no_thermal_throttling' => 'boolean',
        'no_whea_errors' => 'boolean',
        'no_application_crash' => 'boolean',
        'stable_clock_speed' => 'boolean',
        'temperature_within_range' => 'boolean',
        'single_core_score' => 'integer',
        'multi_core_score' => 'integer',
        'benchmark_temp_c' => 'float',
        'benchmark_peak_power_w' => 'float',
        'benchmark_completed' => 'boolean',
        'performance_within_range' => 'boolean',
        'no_thermal_throttling_benchmark' => 'boolean',
        'idle_temp_c' => 'float',
        'load_temp_c' => 'float',
        'ccd_temp_c' => 'float',
        'core_voltage_v' => 'float',
        'avg_effective_clock_mhz' => 'integer',
        'peak_package_power_benchmark_w' => 'float',
        'stability_test_passed' => 'boolean',
        'benchmark_test_passed' => 'boolean',
        'thermal_performance_passed' => 'boolean',
        'clock_stability_passed' => 'boolean',
        'power_delivery_passed' => 'boolean',
        'overall_cpu_validation' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function performanceTest()
    {
        return $this->belongsTo(PerformanceTest::class, 'performance_test_id');
    }
}
