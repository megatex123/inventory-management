<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CraftInspectionItem extends Model
{
    use SoftDeletes;

    protected $table = 'craft_inspection_items';
    protected $guarded = ['id'];

    protected $fillable = [
        'craft_inspection_id',
        'component_type',
        'order_detail_id',
        'fields',
        'model_verified',
        'serial_recorded',
        'factory_seal',
        'qc_pass',
        'inspection_status',
        'inspection_note',
        'inspection_photos',
        'packaging_status',
        'packaging_note',
        'packaging_photos',
        'condition_status',
        'condition_note',
        'condition_photos',
    ];

    protected $casts = [
        'craft_inspection_id' => 'integer',
        'order_detail_id' => 'integer',
        'fields' => 'array',
        'model_verified' => 'boolean',
        'serial_recorded' => 'boolean',
        'factory_seal' => 'boolean',
        'qc_pass' => 'boolean',
        'inspection_photos' => 'array',
        'packaging_photos' => 'array',
        'condition_photos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function craftInspection()
    {
        return $this->belongsTo(CraftInspection::class, 'craft_inspection_id');
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetails::class, 'order_detail_id');
    }
}
