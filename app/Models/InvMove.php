<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvMove extends Model
{
    use SoftDeletes;

    protected $table = 'inv_move';

    protected $fillable = [
        'movement_id',
        'date',
        'master_sku_id',
        'destination_id',
        'order_id',
        'item_name',
        'type',
        'quantity',
        'unit_cost',
    ];

    protected $casts = [
        'master_sku_id' => 'integer',
        'destination_id' => 'integer',
        'order_id' => 'integer',
        'quantity' => 'integer',
        'unit_cost' => 'decimal:4',
        'date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function masterSku()
    {
        return $this->belongsTo(MasterSku::class, 'master_sku_id');
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
