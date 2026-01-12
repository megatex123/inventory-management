<?php

namespace App\Models;

use App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'meeting_id',
        'customer_id',
        'title',
        'meeting_date',
        'meeting_notes',
        'document',
    ];

    protected $dates = ['deleted_at'];

    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }
}
