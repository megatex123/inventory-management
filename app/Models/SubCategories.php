<?php

namespace App\Models;

use App\Models\Categories;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubCategories extends Model
{
    use SoftDeletes;

    protected $table = 'sub_categories';

    protected $fillable = [
        'cat_id',
        'name',
        'code',
        'fee',
    ];

    public function category()
    {
        return $this->belongsTo(Categories::class, 'cat_id');
    }
}
