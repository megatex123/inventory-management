<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestUsbPort extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_usb_ports';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'device_detected' => 'boolean',
        'data_transfer' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function performanceTest()
    {
        return $this->belongsTo(PerformanceTest::class, 'performance_test_id');
    }
}
