<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThreadOrderItem extends Model {
    protected $table = 'thread_order_items';
    protected $guarded = ['id'];

    protected $fillable = [
        'thread_order_id',
        'cable_type',
        'psu_brand',
        'colour_variant',
        'qty',
        'wire_length_cm',
        'sleeve_length_cm',
        'resolved_components',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'qty' => 'integer',
        'wire_length_cm' => 'decimal:2',
        'sleeve_length_cm' => 'decimal:2',
        'resolved_components' => 'array',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function threadOrder() {
        return $this->belongsTo(ThreadOrder::class, 'thread_order_id');
    }
}
