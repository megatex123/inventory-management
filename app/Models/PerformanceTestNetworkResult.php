<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceTestNetworkResult extends Model
{
    use SoftDeletes;

    protected $table = 'performance_test_network_results';
    protected $guarded = ['id'];

    protected $casts = [
        'performance_test_id' => 'integer',
        'wired_network' => 'boolean',
        'lan_detected' => 'boolean',
        'lan_connected' => 'boolean',
        'wifi_adapter_detected' => 'boolean',
        'wifi_connected' => 'boolean',
        'internet_access' => 'boolean',
        'bluetooth_adapter_detected' => 'boolean',
        'bluetooth_pairing_successful' => 'boolean',
        'lan_operating_normally' => 'boolean',
        'wifi_operating_normally' => 'boolean',
        'internet_connection_verified' => 'boolean',
        'bluetooth_pairing_confirmed' => 'boolean',
        'wifi_antenna_installed_correctly' => 'boolean',
        'lan_verification' => 'boolean',
        'wifi_verification' => 'boolean',
        'internet_connectivity' => 'boolean',
        'bluetooth_verification' => 'boolean',
        'overall_network_wireless' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function performanceTest()
    {
        return $this->belongsTo(PerformanceTest::class, 'performance_test_id');
    }
}
