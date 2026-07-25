<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestMemoryResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_memory_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'memory_frequency_mts' => 'integer',
        'total_passes_completed' => 'integer',
        'total_tests_completed' => 'integer',
        'memory_errors_detected' => 'integer',
        'test_completed_successfully' => 'boolean',
        'zero_memory_errors' => 'boolean',
        'stable_expo_xmp_operation' => 'boolean',
        'capacity_status' => 'boolean',
        'configuration_status' => 'boolean',
        'frequency_status' => 'boolean',
        'expo_xmp_status' => 'boolean',
        'memory_stability_test' => 'boolean',
        'memory_frequency_verified' => 'boolean',
        'error_detection' => 'boolean',
        'overall_memory_validation' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function performanceTest()
    {
        return $this->belongsTo(PerformanceTest::class, 'performance_test_id');
    }
}
