<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlusService extends Model {
    use SoftDeletes;
    protected $table = 'plus_services';
    protected $guarded = ['id'];

    protected $fillable = [
        'service_code',
        'name',
        'category',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function orderItems() {
        return $this->hasMany(PlusOrderItem::class, 'plus_service_id');
    }
}
