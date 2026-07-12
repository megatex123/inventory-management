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

    /**
     * Number of years parsed from the `period` string (e.g. "10 years" -> 10).
     */
    public function getPeriodYearsAttribute()
    {
        if (!$this->period) {
            return null;
        }

        preg_match('/(\d+)/', $this->period, $matches);

        return isset($matches[1]) ? (int) $matches[1] : null;
    }
}
