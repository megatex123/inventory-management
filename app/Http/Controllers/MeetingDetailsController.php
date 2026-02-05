<?php

namespace App\Http\Controllers;

use App\Models\MeetingDetails;
use Illuminate\Http\Request;

class MeetingDetailsController extends Controller
{

    public function index()
    {
        return response()->json(
            MeetingDetails::with('meeting.customer')->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'initial_budget' => 'nullable|numeric|min:0',
            'reason' => 'required|in:1,2',
            'play_mode' => 'nullable|in:1,2',
            'include_monitor' => 'nullable|in:1,2',
            'include_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'theme_style' => 'nullable|string|max:191',
            'preference' => 'nullable|string|max:191',
            'exemption' => 'nullable|string|max:191',
            'future_proof' => 'nullable|boolean',
            'case_size' => 'nullable|in:1,2,3',
            'okay_with_aio' => 'nullable|boolean',
            'gpu_sag' => 'nullable|boolean',
            'need_rgb' => 'nullable|boolean',
            'qvcrf_tag' => 'nullable|boolean',
            'qvse' => 'nullable|boolean',
            'qvca' => 'nullable|boolean',
            'qvtd' => 'nullable|boolean',
            'qvtd_notes' => 'nullable|string',
            'target_build_date' => 'nullable|date',
            'target_location' => 'nullable|string|max:191',
        ]);

        $meetingDetail = MeetingDetails::create($validated);

        return response()->json([
            'message' => 'Meeting details created successfully',
            'data' => $meetingDetail
        ], 201);
    }

    // Show single record
    public function show($id)
    {
        // Try to find the meeting detail with all relationships
        $meetingDetail = MeetingDetails::with('meeting')->find($id);

        if (!$meetingDetail) {
            return response()->json(['error' => 'Meeting details not found'], 404);
        }

        return response()->json($meetingDetail);
    }

    // Update record
    public function update(Request $request, $id)
    {
        $meetingDetail = MeetingDetails::findOrFail($id);

        $validated = $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'initial_budget' => 'nullable|numeric|min:0',
            'reason' => 'required|in:1,2',
            'play_mode' => 'nullable|in:1,2',
            'include_monitor' => 'nullable|in:1,2',
            'include_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'theme_style' => 'nullable|string|max:191',
            'preference' => 'nullable|string|max:191',
            'exemption' => 'nullable|string|max:191',
            'future_proof' => 'nullable|boolean',
            'case_size' => 'nullable|in:1,2,3',
            'okay_with_aio' => 'nullable|boolean',
            'gpu_sag' => 'nullable|boolean',
            'need_rgb' => 'nullable|boolean',
            'qvcrf_tag' => 'nullable|boolean',
            'qvse' => 'nullable|boolean',
            'qvca' => 'nullable|boolean',
            'qvtd' => 'nullable|boolean',
            'qvtd_notes' => 'nullable|string',
            'target_build_date' => 'nullable|date',
            'target_location' => 'nullable|string|max:191',
        ]);

        $meetingDetail->update($validated);

        return response()->json([
            'message' => 'Meeting details updated successfully',
            'data' => $meetingDetail
        ]);
    }

    // Delete record
    public function destroy($id)
    {
        $meetingDetail = MeetingDetails::findOrFail($id);
        $meetingDetail->delete();

        return response()->json([
            'message' => 'Meeting details deleted successfully'
        ]);
    }
}
