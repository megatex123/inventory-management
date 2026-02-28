<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductWarranty extends Model
{
    use SoftDeletes;

    protected $table = 'product_warranty';

    protected $fillable = [
        'product_id',
        'product_code',
        'product_name',
        'serial_no',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    // Scope for filtering
    public function scopeFilter($query, $request)
    {
        $search = $request->search;
        $product_id = $request->product_id;
        $product_code = $request->product_code;
        $serial_no = $request->serial_no;
        $date_from = $request->date_from;
        $date_to = $request->date_to;

        $query->when($search ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('product_code', 'like', '%' . $search . '%')
                    ->orWhere('product_name', 'like', '%' . $search . '%')
                    ->orWhere('serial_no', 'like', '%' . $search . '%')
                    ->orWhereHas('product.category',function($query) use ($search){
                        $query->where('naame', 'like', $search);
                    });
            });
        })
        ->when($product_id ?? null, function ($query, $product_id) {
            $query->where('product_id', $product_id);
        })
        ->when($product_code ?? null, function ($query, $product_code) {
            $query->where('product_code', 'like', '%' . $product_code . '%');
        })
        ->when($serial_no ?? null, function ($query, $serial_no) {
            $query->where('serial_no', 'like', '%' . $serial_no . '%');
        })
        ->when($date_from ?? null, function ($query, $date_from) {
            $query->whereDate('created_at', '>=', $date_from);
        })
        ->when($date_to ?? null, function ($query, $date_to) {
            $query->whereDate('created_at', '<=', $date_to);
        });
    }

}
