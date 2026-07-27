<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Customers;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MeetingController extends Controller
{
    public function index()
    {
        return response()->json(
            Meeting::with('customer')->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'   => 'required|exists:customers,id',
            'title'         => 'required|string',
            'meeting_date'  => 'required|date',
            'meeting_notes' => 'nullable',
            'document'      => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120',
        ]);

        if ($request->hasFile('document')) {
            $validated['document'] = $request->file('document')
                ->store('meetings', 'public');
        }

        try {
            $meetingId = BusinessId::next('meetings', 'meeting_id', 'QV-MEET-', 6);

            $meeting = Meeting::create([
                'meeting_id'       => $meetingId,
                'customer_id'      => $request->customer_id,
                'title'            => $request->title,
                'meeting_date'     => $request->meeting_date,
                'meeting_notes'    => $request->meeting_notes,
                'document'         => $request->document,
            ]);

            $meeting->save();

            DB::commit();

            return response()->json([
                'message'     => 'Customer registered successfully',
                'customer_id' => $meetingId,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Meeting failed',
                'error'   => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Meeting created successfully',
            'meeting' => $meeting->load('customer')
        ], 201);
    }

    public function show(Meeting $meeting)
    {
        return response()->json(
            $meeting->load('customer')
        );
    }

    public function update(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'customer_id'   => 'required|exists:customers,id',
            'title'         => 'required|string',
            'meeting_date'  => 'required|date',
            'meeting_notes' => 'nullable|string',
            'document'      => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120',
        ]);

        if ($request->hasFile('document')) {
            if ($meeting->document) {
                Storage::disk('public')->delete($meeting->document);
            }

            $validated['document'] = $request->file('document')
                ->store('meetings', 'public');
        }

        $meeting->update($validated);

        return response()->json([
            'message' => 'Meeting updated successfully',
            'meeting' => $meeting->load('customer')
        ]);
    }

    public function destroy(Meeting $meeting)
    {
        if ($meeting->document) {
            Storage::disk('public')->delete($meeting->document);
        }

        $meeting->delete();

        return response()->json(['message' => 'Meeting deleted']);
    }
}
