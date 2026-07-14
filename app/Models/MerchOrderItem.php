<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchOrderItem extends Model {
    protected $table = 'merch_order_items';
    protected $guarded = ['id'];

    protected $fillable = [
        'merch_order_id',
        'merch_item_id',
        'qty',
        'unit_price',
        'discount_applied',
        'line_total',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_applied' => 'boolean',
        'line_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function merchOrder() {
        return $this->belongsTo(MerchOrder::class, 'merch_order_id');
    }

    public function merchItem() {
        return $this->belongsTo(MerchItem::class, 'merch_item_id');
    }
}
