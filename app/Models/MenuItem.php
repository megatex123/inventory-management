<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'parent_id',
        'type',
        'label',
        'icon',
        'route',
        'sort_order',
        'divider_before',
        'is_active',
    ];

    protected $casts = [
        'divider_before' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }
}
