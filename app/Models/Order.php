<?php

namespace App\Models;

use App\Models\Customers;
use App\Models\Craft;
use App\Models\Serves;
use App\Models\Care;
use App\Models\ServeData;
use App\Models\CareData;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    protected $table = 'order';

    use SoftDeletes;
    protected $fillable = [
        'order_id',
        'invoice_id',
        'customer_id',
        'qty',
        'sub_total',
        'total',
        'order_date',
        'order_month',
        'order_year',
        'categories_id',
        'reject_id',
        'craft_tag_id',
        'no_craft',
        'craft_id',
        'serve_id',
        'care_id',
        'approve',
        'approved_at',
        'is_reason',
        'skip_quivicare',
        'craft_data_id',
        'build_way',
        'tag_along'
    ];

    protected $dates = ['deleted_at'];

    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }

    public function craft()
    {
        return $this->belongsTo(Craft::class);
    }

    public function serve()
    {
        return $this->belongsTo(Serves::class);
    }

    public function serve_data()
    {
        return $this->hasMany(ServeData::class,'order_id','id');
    }

    public function care()
    {
        return $this->belongsTo(Care::class);
    }

    public function care_data()
    {
        return $this->hasMany(CareData::class,'order_id','id');
    }

    public function inv_moves()
    {
        return $this->hasMany(InvMove::class,'order_id','id');
    }

    public function merch_orders()
    {
        return $this->hasMany(MerchOrder::class,'order_id','id');
    }

    public function plus_orders()
    {
        return $this->hasMany(PlusOrder::class,'order_id','id');
    }

    public function thread_orders()
    {
        return $this->hasMany(ThreadOrder::class,'order_id','id');
    }

    public function getServeNameAttribute()
    {
        return optional($this->serve)->name;
    }

    public function getServeColourAttribute()
    {
        return optional($this->serve)->colour;
    }

    public function getServeFeeAttribute()
    {
        return optional($this->serve)->fee;
    }

    public function getCareNameAttribute()
    {
        return optional($this->care)->name;
    }

    public function getCareFeeAttribute()
    {
        return optional($this->care)->fee;
    }

    public function getCraftNameAttribute()
    {
        return optional($this->craft)->name;
    }

    public function getCraftFeeAttribute()
    {
        return optional($this->craft)->fee;
    }

    public function getCustomerNameAttribute()
    {
        return optional($this->customer)->full_name;
    }

    public function getFullNameAttribute()
    {
        return optional($this->customer)->full_name;
    }

    /**
     * Scope a query to only include approved orders.
     */
    public function scopeApproved($query)
    {
        return $query->where('approve', 1);
    }

    /**
     * Scope a query to only include pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('approve', 0);
    }

    /**
     * Scope a query to only include today's orders.
     */
    public function scopeToday($query)
    {
        return $query->where('order_date', date('d/m/Y'));
    }

    /**
     * Calculate the total with fees.
     */
    public function getTotalWithFeesAttribute()
    {
        $total = $this->total;

        if ($this->craft && $this->craft->fee) {
            $total += $this->craft->fee;
        }

        if ($this->serve && $this->serve->fee) {
            $total += $this->serve->fee;
        }

        if ($this->care && $this->care->fee) {
            $total += $this->care->fee;
        }

        return $total;
    }

    /**
     * Get the order status.
     */
    public function getStatusAttribute()
    {
        if ($this->approve) {
            return 'Approved';
        }

        return 'Pending';
    }

    /**
     * Get the status color.
     */
    public function getStatusColorAttribute()
    {
        return $this->approve ? 'success' : 'warning';
    }


    /**
     * Get formatted time remaining.
     */
    public function getTimeRemainingAttribute()
    {
        if (!$this->approved_at) {
            return "Not Approved";
        }

        $today = Carbon::now();
        $approvedAt = Carbon::parse($this->approved_at);
        $expiryDate = $approvedAt->copy()->addMonths(6);

        if ($today->gt($expiryDate)) {
            return "Expired";
        }

        $months = $today->diffInMonths($expiryDate);
        $days = $today->copy()->addMonths($months)->diffInDays($expiryDate);

        return $months . " Months " . $days . " Days";
    }

    /**
     * Get months remaining.
     */
    public function getMonthsRemainingAttribute()
    {
        if (!$this->approved_at) {
            return null;
        }

        $today = Carbon::now();
        $approvedAt = Carbon::parse($this->approved_at);
        $expiryDate = $approvedAt->copy()->addMonths(6);

        if ($today->gt($expiryDate)) {
            return 0;
        }

        return $today->diffInMonths($expiryDate);
    }

    /**
     * Get days remaining.
     */
    public function getDaysRemainingAttribute()
    {
        if (!$this->approved_at) {
            return null;
        }

        $today = Carbon::now();
        $approvedAt = Carbon::parse($this->approved_at);
        $expiryDate = $approvedAt->copy()->addMonths(6);

        if ($today->gt($expiryDate)) {
            return 0;
        }

        $months = $today->diffInMonths($expiryDate);
        return $today->copy()->addMonths($months)->diffInDays($expiryDate);
    }

    /**
     * Get expiry date.
     */
    public function getExpiryDateAttribute()
    {
        if (!$this->approved_at) {
            return null;
        }

        return Carbon::parse($this->approved_at)->addMonths(6);
    }

    /**
     * Check if order is expired.
     */
    public function getIsExpiredAttribute()
    {
        if (!$this->approved_at) {
            return false;
        }

        $today = Carbon::now();
        $expiryDate = $this->expiry_date;

        return $today->gt($expiryDate);
    }
}
