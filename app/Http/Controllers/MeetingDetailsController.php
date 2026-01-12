<?php

namespace App\Http\Controllers;

use App\Models\MeetingDetails;
use Illuminate\Http\Request;

class MeetingDetailsController extends Controller
{

    public function index()
    {
        return response()->json(
            MeetingDetails::with('meeting')->latest()->get()
        );
    }

    public function store(Request $request)
    {
        if ($request->filled('target_location')) {
            $location = strtolower($request->target_location);
            $request->merge([
                'qvcrf_tag' => (!str_contains($location, 'klang valley')) ? 1 : 0
            ]);
        }

        $validated = $request->validate([
            'meeting_id'        => 'required|exists:meetings,id',
            'initial_budget'    => 'required|numeric',
            'reason'            => 'required|integer|in:1,2', // 1: Work, 2: Gaming
            // play_mode is required only if reason is 2 (Gaming)
            'play_mode'         => 'required_if:reason,2|nullable|integer|in:1,2',
            'notes'             => 'nullable|string',
            // If qvse or qvca is true (1), notes must be provided
            'notes'             => 'required_if:qvse,1|required_if:qvca,1|nullable|string',
            'include_monitor'   => 'nullable|string',
            'theme_style'       => 'nullable|string',
            'preference'        => 'nullable|string',
            'exemption'         => 'nullable|boolean',
            'future_proof'      => 'nullable|boolean',
            'case_size'         => 'nullable|integer',
            'okay_with_aio'     => 'nullable|boolean',
            'need_rgb'          => 'nullable|boolean',
            'gpu_sag'           => 'nullable|boolean',
            'qvcrf_tag'         => 'nullable|boolean',
            'qvse'              => 'nullable|boolean',
            'qvca'              => 'nullable|boolean',
            'qvtd'              => 'nullable|boolean',
            'target_build_date' => 'nullable|date',
            'target_location'   => 'nullable|string',
        ]);

        // Force play_mode to null if reason is Work
        if ($validated['reason'] == 1) {
            $validated['play_mode'] = null;
        }

        $details = MeetingDetails::create($validated);

        return response()->json([
            'message' => 'Meeting details saved successfully',
            'data' => $details->load('meeting')
        ], 201);
    }

    public function show(MeetingDetails $meeting)
    {
        return response()->json(
            $meeting->load('meeting')
        );
    }

    public function update(Request $request, MeetingDetails $meeting)
    {
        $validated = $request->validate([
            'meeting_id'        => 'required|exists:meetings,id',
            'initial_budget'    => 'required|numeric',
            'reason'            => 'required|integer',
            'play_mode'         => 'nullable|integer',
            'include_monitor'   => 'nullable|string',
            'notes'             => 'nullable|string',
            'theme_style'       => 'nullable|string',
            'preference'        => 'nullable|string',
            'exemption'         => 'nullable|boolean',
            'future_proof'      => 'nullable|boolean',
            'case_size'         => 'nullable|boolean',
            'okay_with_aio'     => 'nullable|boolean',
            'need_rgb'          => 'nullable|boolean',
            'gpu_sag'           => 'nullable|boolean',
            'qvcrf_tag'         => 'nullable|boolean',
            'qvse'              => 'nullable|boolean',
            'qvca'              => 'nullable|boolean',
            'qvtd'              => 'nullable|boolean',
            'target_build_date' => 'nullable|date',
            'target_location'   => 'nullable|string',
        ]);

        $meeting->update($validated);

        return response()->json([
            'message' => 'Meeting updated successfully',
            'data' => $meeting->load('meeting')
        ]);
    }

    public function destroy(MeetingDetails $meeting)
    {
        $meeting->delete();

        return response()->json(['message' => 'Meeting deleted']);
    }
}
