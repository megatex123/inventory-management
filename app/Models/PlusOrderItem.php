<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlusOrderItem extends Model {
    protected $table = 'plus_order_items';
    protected $guarded = ['id'];

    protected $fillable = [
        'plus_order_id',
        'plus_service_id',
        'qty',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function plusOrder() {
        return $this->belongsTo(PlusOrder::class, 'plus_order_id');
    }

    public function plusService() {
        return $this->belongsTo(PlusService::class, 'plus_service_id');
    }
}
