<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestGpuResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_gpu_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'vram_test' => 'boolean',
        'avg_temp_c' => 'float',
        'max_temp_c' => 'float',
        'max_hotspot_temp_c' => 'float',
        'avg_clock_mhz' => 'integer',
        'peak_power_draw_w' => 'float',
        'thermal_throttling' => 'boolean',
        'visual_artifacts' => 'boolean',
        'driver_crash' => 'boolean',
        'no_visual_artifacts' => 'boolean',
        'no_driver_crash' => 'boolean',
        'stable_clock_speed' => 'boolean',
        'temperature_within_range' => 'boolean',
        'gpu_score' => 'integer',
        'overall_score' => 'integer',
        'benchmark_temp_c' => 'float',
        'benchmark_peak_power_w' => 'float',
        'benchmark_completed' => 'boolean',
        'performance_within_range' => 'boolean',
        'no_performance_anomalies' => 'boolean',
        'idle_temp_c' => 'float',
        'load_temp_c' => 'float',
        'hotspot_temp_c' => 'float',
        'core_clock_mhz' => 'integer',
        'memory_clock_mhz' => 'integer',
        'power_draw_w' => 'float',
        'fan_speed_rpm' => 'integer',
        'stability_test_passed' => 'boolean',
        'benchmark_test_passed' => 'boolean',
        'thermal_performance_passed' => 'boolean',
        'clock_stability_passed' => 'boolean',
        'cooling_performance_passed' => 'boolean',
        'overall_gpu_validation' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function performanceTest()
    {
        return $this->belongsTo(PerformanceTest::class, 'performance_test_id');
    }
}
