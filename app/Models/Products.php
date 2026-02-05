<?php

namespace App\Models;
use App\Models\Categories;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $fillable = [
        'cat_id',
        'category_name',
        'product_name',
        'product_code',
        'root',
        'price',
        'price_updated_at',
        'supplier_id',
        'buying_date',
        'image',
        'product_qty',
    ];

    public function category()
    {
        return $this->belongsTo(Categories::class, 'car_id');
    }
}
