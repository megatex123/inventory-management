<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CareWarranty extends Model
{
    use SoftDeletes;

    protected $table = 'care_warranty';

    protected $fillable = [
        'care_warranty_id',
        'care_data_id',
        'care_invoice_id',
        'product_id',
        'category_id',
        'eligible_warranty',
        'eligible_qvca',
        'i_qvca_id',
        'spare_item_name',
        'spare_category_id',
        'date_start',
        'loan_date_end',
        'reset_status'
    ];

    protected $casts = [
        'reset_status' => 'boolean',
        'eligible_warranty' => 'boolean',
        'eligible_qvca' => 'boolean',
        'date_start' => 'date',
        'loan_date_end' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the care data that owns the Warranty
     */
    public function careData()
    {
        return $this->belongsTo(CareData::class, 'care_data_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    /**
     * Get the category associated with the item
     */
    public function category()
    {
        return $this->belongsTo(Categories::class, 'category_id');
    }

    /**
     * Get the spare category associated with the item
     */
    public function spareCategory()
    {
        return $this->belongsTo(Categories::class, 'spare_category_id');
    }

    /**
     * Scope a query to only include active warranties (date_start within last 3 years)
     */
    public function scopeActiveWaranty($query)
    {
        return $query->where('date_start', '>=', now()->subYears(3));
    }

    /**
     * Scope a query to only include expired warranties
     */
    public function scopeExpiredWaranty($query)
    {
        return $query->where('date_start', '<', now()->subYears(3));
    }

    /**
     * Check if warranty is active
     */
    public function isWarantyActive(): bool
    {
        return $this->date_start && $this->date_start >= now()->subYears(3);
    }

    /**
     * Get warranty status
     */
    public function getWarantyStatusAttribute(): string
    {
        if (!$this->date_start) {
            return 'unknown';
        }

        return $this->isWarantyActive() ? 'active' : 'expired';
    }
}
