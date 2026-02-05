<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CareData extends Model
{

    use SoftDeletes;

    protected $fillable = [
        'care_id',
        'customer_id',
        'order_id',
        'lkp_care_id',
        'total_part',
        'price',
        'update_membership',
    ];

    protected $casts = [
        'update_membership' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function care()
    {
        return $this->belongsTo(Care::class, 'lkp_care_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
