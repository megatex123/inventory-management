<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServeData extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'serve_id',
        'qvse_cid',
        'customer_id',
        'order_id',
        'lkp_serve_id',
        'start_serve_enabled',
        'start_serve_date',
        'start_serve_timestamp',
        'upgrade_pce_enabled',
        'upgrade_pce_notes',
    ];

    protected $casts = [
        'start_serve_enabled' => 'boolean',
        'upgrade_pce_enabled' => 'boolean',
        'start_serve_timestamp' => 'integer',
        'start_serve_date' => 'datetime',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'start_serve_date'
    ];

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function serve()
    {
        return $this->belongsTo(Serves::class, 'lkp_serve_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // Accessor for formatted start_serve_date
    public function getFormattedStartServeDateAttribute()
    {
        return $this->start_serve_date ?
            $this->start_serve_date->format('Y-m-d H:i:s') :
            null;
    }

    // Accessor for readable status
    public function getStatusAttribute()
    {
        if ($this->start_serve_enabled) {
            return $this->upgrade_pce_enabled ?
                'Active with Upgrade' :
                'Active';
        }
        return 'Not Started';
    }

    // Accessor for display upgrade status
    public function getUpgradeStatusAttribute()
    {
        return $this->upgrade_pce_enabled ?
            'Enabled' :
            'Disabled';
    }

    // Scope for active serves
    public function scopeActive($query)
    {
        return $query->where('start_serve_enabled', true);
    }

    // Scope for serves with upgrade
    public function scopeWithUpgrade($query)
    {
        return $query->where('upgrade_pce_enabled', true);
    }

    // Scope for today's serves
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Scope for this month
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    // Find by serve_id
    public function scopeFindByServeId($query, $serveId)
    {
        return $query->where('serve_id', $serveId)->first();
    }

    // Find by qvse_cid
    public function scopeFindByQvseCid($query, $qvseCid)
    {
        return $query->where('qvse_cid', $qvseCid)->first();
    }

    // Get next serve ID
    public static function getNextServeId()
    {
        $totalServes = self::count();
        $nextId = $totalServes + 1;
        $serveNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
        return "QVSE-{$serveNumber}";
    }

    // Get statistics
    public static function getStatistics()
    {
        return [
            'total_serves' => self::count(),
            'today_serves' => self::today()->count(),
            'total_upgrades' => self::withUpgrade()->count(),
            'unique_customers' => self::distinct('customer_id')->count('customer_id'),
            'total_revenue' => self::join('orders', 'serve_data.order_id', '=', 'orders.id')
                                 ->sum('orders.total')
        ];
    }

    // Check if serve is active
    public function isActive()
    {
        return $this->start_serve_enabled;
    }

    // Check if upgrade is enabled
    public function hasUpgrade()
    {
        return $this->upgrade_pce_enabled;
    }

    // Get customer name with fallback
    public function getCustomerNameAttribute()
    {
        return $this->customer ? $this->customer->full_name : 'N/A';
    }

    // Get order total with fallback
    public function getOrderTotalAttribute()
    {
        return $this->order ? $this->order->total : 0;
    }

    // Get serve fee with fallback
    public function getServeFeeAttribute()
    {
        return $this->serve ? $this->serve->fee : 0;
    }

    // Get serve name with fallback
    public function getServeNameAttribute()
    {
        return $this->serve ? $this->serve->name : 'N/A';
    }
}
