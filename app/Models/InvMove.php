<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvMove extends Model {
    use SoftDeletes;
    protected $table = 'inv_move';
    protected $guarded = ['id'];

    protected $fillable = [
        'item_name',
        'date',
        'master_sku_id',
        'destination_id',
        'reference_id',
        'unit_cost',
        'quantity',
        'type',
    ];

    protected $casts = [
        'master_sku_id' => 'integer',
        'destination_id' => 'integer',
        'reference_id' => 'integer',
        'date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function product_raw() {
        return $this->belongsTo(ProductRaw::class, 'product_raw_id');
    }

    public function suppliers() {
        return $this->belongsTo(Suppliers::class, 'supplier_id');
    }
}
