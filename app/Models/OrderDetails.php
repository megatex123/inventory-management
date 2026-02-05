<?php

namespace App\Models;

use App\Models\Products;

use Illuminate\Database\Eloquent\Model;

class OrderDetails extends Model
{

    protected $table = 'order_details';

    protected $fillable = [
        'order_id',
        'pro_id',
        'pro_qty',
        'pro_price',
        'sub_total'
    ];

    protected $casts = [
        'pro_qty' => 'integer',
        'pro_price' => 'decimal:2',
        'sub_total' => 'decimal:2'
    ];

    /**
     * Get the order that owns the detail.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the product that owns the detail.
     */
    public function product()
    {
        return $this->belongsTo(Products::class, 'pro_id');
    }

    /**
     * Get the category through product.
     */
    public function category()
    {
        return $this->hasOneThrough(
            Categories::class,
            Products::class,
            'id', // Foreign key on Product table
            'id', // Foreign key on Category table
            'pro_id', // Local key on OrderDetail table
            'cat_id' // Local key on Product table
        );
    }

    /**
     * Calculate subtotal automatically.
     */
    public function calculateSubTotal()
    {
        $this->sub_total = $this->pro_qty * $this->pro_price;
        return $this;
    }

    /**
     * Update product stock when order detail is created.
     */
    protected static function boot()
    {
        parent::boot();

        // When order detail is created, update product stock
        static::created(function ($orderDetail) {
            if ($orderDetail->product) {
                $orderDetail->product->decrement('product_qty', $orderDetail->pro_qty);
            }
        });

        // When order detail is updated, adjust product stock
        static::updated(function ($orderDetail) {
            if ($orderDetail->product) {
                $originalQty = $orderDetail->getOriginal('pro_qty');
                $newQty = $orderDetail->pro_qty;
                $difference = $newQty - $originalQty;

                if ($difference != 0) {
                    $orderDetail->product->decrement('product_qty', $difference);
                }
            }
        });

        // When order detail is deleted, restore product stock
        static::deleted(function ($orderDetail) {
            if ($orderDetail->product) {
                $orderDetail->product->increment('product_qty', $orderDetail->pro_qty);
            }
        });
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute()
    {
        return 'RM ' . number_format($this->pro_price, 2);
    }

    /**
     * Get formatted subtotal.
     */
    public function getFormattedSubTotalAttribute()
    {
        return 'RM ' . number_format($this->sub_total, 2);
    }

    /**
     * Scope a query to include product information.
     */
    public function scopeWithProduct($query)
    {
        return $query->join('products', 'order_details.pro_id', '=', 'products.id')
                    ->select(
                        'order_details.*',
                        'products.product_name',
                        'products.product_code',
                        'products.image',
                        'products.cat_id'
                    );
    }

    /**
     * Scope a query to include category information.
     */
    public function scopeWithCategory($query)
    {
        return $query->join('products', 'order_details.pro_id', '=', 'products.id')
                    ->join('categories', 'products.cat_id', '=', 'categories.id')
                    ->select(
                        'order_details.*',
                        'products.product_name',
                        'products.product_code',
                        'products.image',
                        'categories.name as category_name'
                    );
    }

    /**
     * Scope a query to include all related information.
     */
    public function scopeWithAll($query)
    {
        return $query->join('products', 'order_details.pro_id', '=', 'products.id')
                    ->leftJoin('categories', 'products.cat_id', '=', 'categories.id')
                    ->leftJoin('order', 'order_details.order_id', '=', 'order.id')
                    ->leftJoin('craft', 'order.craft_id', '=', 'craft.id')
                    ->leftJoin('serves', 'order.serve_id', '=', 'serves.id')
                    ->leftJoin('care', 'order.care_id', '=', 'care.id')
                    ->select(
                        'order_details.*',
                        'products.product_name',
                        'products.product_code',
                        'products.image',
                        'categories.name as category_name',
                        'craft.name as craft_name',
                        'serves.name as serve_name',
                        'care.name as care_name'
                    );
    }
}
