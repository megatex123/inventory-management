<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestStorageResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_storage_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'drive_temp_c' => 'float',
        'health_status_good' => 'boolean',
        'drive_detected_correctly' => 'boolean',
        'firmware_verified' => 'boolean',
        'temperature_within_range' => 'boolean',
        'sequential_read_speed_mbs' => 'float',
        'sequential_write_speed_mbs' => 'float',
        'benchmark_completed' => 'boolean',
        'read_performance_within_range' => 'boolean',
        'write_performance_within_range' => 'boolean',
        'driver_status' => 'boolean',
        'storage_health_verification' => 'boolean',
        'firmware_verification' => 'boolean',
        'performance_verification' => 'boolean',
        'temperature_verification' => 'boolean',
        'overall_storage_validation' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function performanceTest()
    {
        return $this->belongsTo(PerformanceTest::class, 'performance_test_id');
    }
}
