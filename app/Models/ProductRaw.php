<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductRaw extends Model
{
    use SoftDeletes;
    protected $table = 'product_raw';
    protected $guarded = ['id'];

    protected $fillable = [
        'product_id',
        'product_code',
        'cat_id',
        'category_name',
        'sub_cat_id',
        'brand_id',
        'product_name',
        'supplier_id',
        'buying_date',
        'image',
        'product_qty',
        'product_loan',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'cat_id' => 'integer',
        'sub_cat_id' => 'integer',
        'brand_id' => 'integer',
        'supplier_id' => 'integer',
        'product_qty' => 'integer',
        'product_loan' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
