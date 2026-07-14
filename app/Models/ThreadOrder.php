<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThreadOrder extends Model {
    use SoftDeletes;
    protected $table = 'thread_orders';
    protected $guarded = ['id'];

    protected $fillable = [
        'thread_order_id',
        'customer_id',
        'order_id',
        'status',
        'completed_at',
        'warranty_ends_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'order_id' => 'integer',
        'completed_at' => 'datetime',
        'warranty_ends_at' => 'datetime',
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
        return $this->hasMany(ThreadOrderItem::class, 'thread_order_id');
    }
}
