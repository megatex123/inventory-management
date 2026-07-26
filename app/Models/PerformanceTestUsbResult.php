<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestUsbResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_usb_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'front_usb_ports_operational' => 'boolean',
        'rear_usb_ports_operational' => 'boolean',
        'stable_device_detection' => 'boolean',
        'successful_data_transfer' => 'boolean',
        'front_usb_verification' => 'boolean',
        'rear_usb_verification' => 'boolean',
        'data_transfer_verification' => 'boolean',
        'overall_usb_ports' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function performanceTest()
    {
        return $this->belongsTo(PerformanceTest::class, 'performance_test_id');
    }
}
