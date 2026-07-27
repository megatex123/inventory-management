<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model {
    use SoftDeletes;
    protected $table = 'refunds';
    protected $guarded = ['id'];

    protected $fillable = [
        'refund_id',
        'customer_id',
        'order_id',
        'plus_order_id',
        'merch_order_id',
        'thread_order_id',
        'refund_amount',
        'deposit_amount',
        'payment_type',
        'cash_journal',
        'notes',
        'refunded_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'order_id' => 'integer',
        'plus_order_id' => 'integer',
        'merch_order_id' => 'integer',
        'thread_order_id' => 'integer',
        'refund_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'cash_journal' => 'boolean',
        'refunded_at' => 'datetime',
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

    public function plusOrder() {
        return $this->belongsTo(PlusOrder::class, 'plus_order_id');
    }

    public function merchOrder() {
        return $this->belongsTo(MerchOrder::class, 'merch_order_id');
    }

    public function threadOrder() {
        return $this->belongsTo(ThreadOrder::class, 'thread_order_id');
    }
}
