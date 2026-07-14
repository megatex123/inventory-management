<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlusOrder extends Model {
    use SoftDeletes;
    protected $table = 'plus_orders';
    protected $guarded = ['id'];

    protected $fillable = [
        'plus_order_id',
        'customer_id',
        'order_id',
        'status',
        'scheduled_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'order_id' => 'integer',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
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
        return $this->hasMany(PlusOrderItem::class, 'plus_order_id');
    }
}
