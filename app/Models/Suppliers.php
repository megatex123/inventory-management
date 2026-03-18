<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suppliers extends Model
{
    protected $fillable = [
        'name',
        'supplier_id',
        'email',
        'phone',
        'shopname',
        'address',
        'photo'
    ];
}
