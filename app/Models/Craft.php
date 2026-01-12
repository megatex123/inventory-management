<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Craft extends Model
{
    protected $table = 'craft';
    use SoftDeletes;
    protected $fillable = [
        'name',
        'code',
        'fee'
    ];
}
