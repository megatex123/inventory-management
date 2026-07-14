<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchOrder extends Model {
    use SoftDeletes;
    protected $table = 'merch_orders';
    protected $guarded = ['id'];

    protected $fillable = [
        'merch_order_id',
        'customer_id',
        'order_id',
        'status',
        'purchased_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'order_id' => 'integer',
        'purchased_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function customer() {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function order() {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function items() {
        return $this->hasMany(MerchOrderItem::class, 'merch_order_id');
    }
}
