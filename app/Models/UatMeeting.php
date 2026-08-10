<?php

namespace App\Models;
use App\Models\Meeting;
use App\Models\MeetingDetails;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

class UatMeeting extends Model
{
    protected $table = 'uat_meeting';

    protected $fillable = [
        'meeting_id',
        'uat_id',
        'requirement_id',
        'order_id',
        'budget_change',
        'parts_changes',
        'add_on_parts',
        'parts_notes',
        'case_size_change',
        'overall_notes',
        'quivicare_change',
        'quivithread_change',
        'changes_required',
        'new_proposal_required',
        'follow_up_required',
        'customer_approval',
        'target_build_date',
        'target_location',
    ];

    protected $casts = [
        'changes_required'      => 'boolean',
        'new_proposal_required' => 'boolean',
        'follow_up_required'    => 'boolean',
        'target_build_date'     => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function requirementMeeting()
    {
        return $this->belongsTo(MeetingDetails::class, 'requirement_id', 'requirement_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
