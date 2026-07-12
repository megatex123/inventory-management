<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerProgress extends Model
{
    use SoftDeletes;

    protected $table = 'customer_progress';

    const STATUSES = ['pending', 'in_progress', 'completed', 'on_hold'];

    protected $fillable = [
        'customer_id',
        'order_id',
        'title',
        'description',
        'status',
        'progress_percentage',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'updated_by',
        'completed_at',
    ];

    protected $casts = [
        'progress_percentage' => 'integer',
        'file_size' => 'integer',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function getFileSizeLabelAttribute()
    {
        $bytes = $this->file_size;

        if ($bytes === null) {
            return 'N/A';
        }

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
}
