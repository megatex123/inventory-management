<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchItem extends Model {
    use SoftDeletes;
    protected $table = 'merch_items';
    protected $guarded = ['id'];

    protected $fillable = [
        'item_code',
        'sku_code',
        'name',
        'retail_price',
        'member_discount_price',
        'is_exclusive',
        'status',
    ];

    protected $casts = [
        'retail_price' => 'decimal:2',
        'member_discount_price' => 'decimal:2',
        'is_exclusive' => 'boolean',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function masterSku() {
        return $this->belongsTo(MasterSku::class, 'sku_code', 'sku_code');
    }

    public function orderItems() {
        return $this->hasMany(MerchOrderItem::class, 'merch_item_id');
    }
}
