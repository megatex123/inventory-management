<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestCoolingPerformanceResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_cooling_performance_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'ambient_temp_c' => 'float',
        'cpu_idle_temp_c' => 'float',
        'cpu_load_temp_c' => 'float',
        'gpu_idle_temp_c' => 'float',
        'gpu_load_temp_c' => 'float',
        'vrm_idle_temp_c' => 'float',
        'vrm_load_temp_c' => 'float',
        'chipset_idle_temp_c' => 'float',
        'chipset_load_temp_c' => 'float',
        'cpu_temp_within_range' => 'boolean',
        'gpu_temp_within_range' => 'boolean',
        'vrm_temp_within_range' => 'boolean',
        'chipset_temp_within_range' => 'boolean',
        'cooling_operating_normally' => 'boolean',
        'no_thermal_throttling' => 'boolean',
        'temps_stable_under_load' => 'boolean',
        'cpu_cooling_performance' => 'boolean',
        'gpu_cooling_performance' => 'boolean',
        'motherboard_cooling_performance' => 'boolean',
        'overall_cpu_cooling_performance' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function performanceTest()
    {
        return $this->belongsTo(PerformanceTest::class, 'performance_test_id');
    }
}
