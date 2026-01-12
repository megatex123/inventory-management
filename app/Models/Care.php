<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Care extends Model
{
    protected $table = 'care';
    use SoftDeletes;
    protected $fillable = [
        'name',
        'cade',
        'fee',
        'period',
    ];
}
