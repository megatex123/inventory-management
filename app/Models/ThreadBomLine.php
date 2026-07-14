<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThreadBomLine extends Model {
    protected $table = 'thread_bom_lines';
    protected $guarded = ['id'];

    protected $fillable = [
        'thread_bom_header_id',
        'sku_code',
        'item_name',
        'qty_per_cable',
        'unit_cost',
    ];

    protected $casts = [
        'qty_per_cable' => 'integer',
        'unit_cost' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function header() {
        return $this->belongsTo(ThreadBomHeader::class, 'thread_bom_header_id');
    }

    public function masterSku() {
        return $this->belongsTo(MasterSku::class, 'sku_code', 'sku_code');
    }
}
