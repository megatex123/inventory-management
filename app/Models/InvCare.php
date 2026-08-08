<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvCare extends Model {
    use SoftDeletes;
    protected $table = 'inv_care';
    protected $guarded = ['id'];

    protected $fillable = [
        'inv_care',
        'care_id',
        'sku_code',
        'item_name',
        'unit_cost',
        'max_stock',
        'current_stock',
        'category',
        'status',
        'generate_id',
        'serial_label',
        'warranty_starts',
        'warranty_duration',
        'warranty_ends',
        'manufacturer',
    ];

    protected $casts = [
        'care_id' => 'integer',
        'unit_cost' => 'decimal:2',
        'max_stock' => 'integer',
        'current_stock' => 'integer',
        'category' => 'integer',
        'status' => 'integer',
        'generate_id' => 'integer',
        'serial_label' => 'integer',
        'warranty_starts' => 'datetime',
        'warranty_duration' => 'integer',
        'warranty_ends' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function masterSku() {
        return $this->belongsTo(MasterSku::class, 'sku_code', 'sku_code');
    }

    public function careData() {
        return $this->belongsTo(CareData::class, 'care_id');
    }

    public function categoryLookup() {
        return $this->belongsTo(Categories::class, 'category');
    }
}
