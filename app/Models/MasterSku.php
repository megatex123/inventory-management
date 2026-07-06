<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterSku extends Model {
    use SoftDeletes;
    protected $table = 'master_sku';
    protected $guarded = ['id'];

    protected $fillable = [
        'sku_code',
        'supplier_id',
        'product_raw_id',
        'product_name',
        'from',
        'cost',
        'unit_type',
        'lkp_status_sku',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'product_raw_id' => 'integer',
        'lkp_status_sku' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function suppliers() {
        return $this->belongsTo(Suppliers::class, 'supplier_id');
    }

    public function productRaw() {
        return $this->belongsTo(ProductRaw::class, 'product_raw_id');
    }

    public function invCare() {
        return $this->hasMany(InvCare::class, 'sku_code', 'sku_code');
    }

    public function invExclServe() {
        return $this->hasMany(InvExclServe::class, 'sku_code', 'sku_code');
    }
}
