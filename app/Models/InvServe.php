<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvServe extends Model {
    use SoftDeletes;
    // The real, original table -- see 2026_08_18_000000_restore_inv_excl_serve_drop_inv_serve.
    protected $table = 'inv_excl_serve';
    protected $guarded = ['id'];

    protected $fillable = [
        'inv_excl_serve',
        'sku_code',
        'serve_data_id',
        'item_name',
        'unit_cost',
        'max_stock',
        'current_stock',
        'to_restock',
        'status',
        'generate_id',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'max_stock' => 'integer',
        'current_stock' => 'integer',
        'to_restock' => 'integer',
        'status' => 'integer',
        'generate_id' => 'integer',
        'serve_data_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function masterSku() {
        return $this->belongsTo(MasterSku::class, 'sku_code', 'sku_code');
    }

    public function serveData() {
        return $this->belongsTo(ServeData::class, 'serve_data_id');
    }
}
