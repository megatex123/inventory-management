<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestDisplayResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_display_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'display_detected' => 'boolean',
        'refresh_rate_hz' => 'integer',
        'display_detected_successfully' => 'boolean',
        'correct_resolution_applied' => 'boolean',
        'correct_refresh_rate_applied' => 'boolean',
        'hdr_functions_correctly' => 'boolean',
        'stable_video_output' => 'boolean',
        'display_detection' => 'boolean',
        'resolution_verification' => 'boolean',
        'refresh_rate_verification' => 'boolean',
        'video_output_verification' => 'boolean',
        'overall_display_output' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function performanceTest()
    {
        return $this->belongsTo(PerformanceTest::class, 'performance_test_id');
    }
}
