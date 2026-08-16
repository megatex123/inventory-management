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
        'category',
        'status',
        'generate_id',
        'serial_number',
        'warranty_starts',
        'warranty_duration',
        'warranty_ends',
        'manufacturer',
    ];

    protected $casts = [
        'care_id' => 'integer',
        'unit_cost' => 'decimal:2',
        'category' => 'integer',
        'status' => 'integer',
        'generate_id' => 'integer',
        'warranty_starts' => 'datetime',
        'warranty_duration' => 'integer',
        'warranty_ends' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // 1 Active, 2 Discontinued, 3 Deprecated, 4 Testing, 5 Reserved,
    // 6 Out of Stock, 7 Archived, 8 Occupied (this specific serialized
    // unit has already been used as a warranty replacement).
    const STATUS_ACTIVE = 1;
    const STATUS_OCCUPIED = 8;

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
