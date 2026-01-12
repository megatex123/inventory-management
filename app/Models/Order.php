<?php

namespace App\Models;

use App\Models\Customers;
use App\Models\Craft;
use App\Models\Serves;
use App\Models\Care;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    protected $table = 'order';

    use SoftDeletes;
    protected $fillable = [
        'customer_id',
        'qty',
        'sub_total',
        'total',
        'order_date',
        'order_month',
        'order_year',
        'categories_id',
        'serve_id',
        'care_id',
        'approve',
        'approved_at'
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

    public function care()
    {
        return $this->belongsTo(Care::class);
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
}
