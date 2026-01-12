<?php

namespace App\Models;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Model;

class MeetingDetails extends Model
{
    protected $fillable = [
        'meeting_id',
        'initial_budget',
        'reason',
        'play_mode',
        'include_monitor',
        'theme_style',
        'preference',
        'exemption',
        'future_proof',
        'case_size',
        'okay_with_aio',
        'need_rgb',
        'gpu_sag',
        'qvcrf_tag',
        'qvse',
        'qvca',
        'qvtd',
        'target_build_date',
        'target_location',
    ];

    protected $casts = [
        'initial_budget'    => 'float',
        'reason'            => 'integer',
        'play_mode'         => 'integer',
        'future_proof'      => 'boolean',
        'okay_with_aio'     => 'boolean',
        'need_rgb'          => 'boolean',
        'gpu_sag'           => 'boolean',
        'qvcrf_tag'         => 'boolean',
        'qvse'              => 'boolean',
        'qvca'              => 'boolean',
        'qvtd'              => 'boolean',
        'target_build_date' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }
}
