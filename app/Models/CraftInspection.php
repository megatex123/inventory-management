<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CraftInspection extends Model
{
    use SoftDeletes;

    protected $table = 'craft_inspections';
    protected $guarded = ['id'];

    protected $fillable = [
        'order_id',
        'phase',
        'round',
        'status',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'phase' => 'integer',
        'round' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function items()
    {
        return $this->hasMany(CraftInspectionItem::class, 'craft_inspection_id');
    }
}
