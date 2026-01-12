<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Serves extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'code',
        'colour',
        'fee',
        'description',
    ];
}
