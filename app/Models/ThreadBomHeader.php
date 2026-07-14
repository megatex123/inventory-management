<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThreadBomHeader extends Model {
    use SoftDeletes;
    protected $table = 'thread_bom_headers';
    protected $guarded = ['id'];

    protected $fillable = [
        'psu_brand',
        'cable_type',
        'colour_variant',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function lines() {
        return $this->hasMany(ThreadBomLine::class, 'thread_bom_header_id');
    }
}
