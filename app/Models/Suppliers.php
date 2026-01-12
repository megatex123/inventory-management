<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suppliers extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'photo',
        'shopname',
    ];
}
