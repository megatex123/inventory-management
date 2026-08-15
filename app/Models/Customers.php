<?php

namespace App\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customers extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'full_name',
        'preferred_name',
        'email',
        'phone',
        'address',
        'contact_method',
        'contact_other',
        'photo',
        'feedback',
        'hear_about',
        'hear_about_other',
        'referred_by',
        'consent',
        'approve',
        'approved_at',
        'update_token',
        'update_used'
    ];

    protected $dates = ['deleted_at'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function careData()
    {
        return $this->hasMany(CareData::class, 'customer_id');
    }

    public function serveData()
    {
        return $this->hasMany(ServeData::class, 'customer_id');
    }

    public function progressEntries()
    {
        return $this->hasMany(CustomerProgress::class, 'customer_id');
    }
}
