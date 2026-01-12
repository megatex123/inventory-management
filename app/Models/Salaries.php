<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salaries extends Model
{
    protected $fillable = [
        'emp_id',
        'amount',
        'salary_date',
        'salary_month',
        'salary_year',
    ];
}
