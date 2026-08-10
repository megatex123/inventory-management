<?php

namespace App\Http\Controllers;

use App\Models\UatMeeting;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;

class UatMeetingController extends Controller
{
    use FiltersSortsAndPaginates;

    public function index(Request $request)
    {
        $query = UatMeeting::with(['meeting.customer', 'requirementMeeting', 'order']);

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');

            $query->where(function ($q) use ($escaped) {
                $q->where('uat_id', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('budget_change', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('parts_changes', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('overall_notes', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('requirement_id', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('order_id', 'LIKE', '%' . $escaped . '%')
                    ->orWhereHas('meeting', function ($mq) use ($escaped) {
                        $mq->where('meeting_id', 'LIKE', '%' . $escaped . '%');
                    });
            });
        }

        $this->applyEqualsFilter($query, $request, 'customerApproval', 'customer_approval');
        $this->applyEqualsFilter($query, $request, 'changesRequired', 'changes_required');
        $this->applyEqualsFilter($query, $request, 'quivicareChange', 'quivicare_change');

        $this->resolveSortAndApply($query, $request, ['target_build_date', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request);
        $paginated = $query->paginate($perPage);

        return $this->paginatedResponse($paginated);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'requirement_id' => 'nullable|string|exists:meeting_details,requirement_id',
            'order_id' => 'nullable|string|exists:order,order_id',
            'budget_change' => 'nullable|string',
            'parts_changes' => 'nullable|string',
            'add_on_parts' => 'nullable|string',
            'parts_notes' => 'nullable|string',
            'case_size_change' => 'nullable|string|max:191',
            'overall_notes' => 'nullable|string',
            'quivicare_change' => 'nullable|in:no_change,add,remove,change_plan',
            'quivithread_change' => 'nullable|in:no_change,add,remove,change_option',
            'changes_required' => 'nullable|boolean',
            'new_proposal_required' => 'nullable|boolean',
            'customer_approval' => 'nullable|in:pending,approved,rejected',
            'follow_up_required' => 'nullable|boolean',
            'target_build_date' => 'nullable|date',
            'target_location' => 'nullable|string|max:191',
        ]);

        // uat_id is always server-generated -- never accepted from the client.
        $validated['uat_id'] = BusinessId::next('uat_meeting', 'uat_id', 'UAT-', 6);

        $uatMeeting = UatMeeting::create($validated);

        return response()->json([
            'message' => 'UAT meeting created successfully',
            'data' => $uatMeeting->load(['meeting.customer', 'requirementMeeting', 'order']),
        ], 201);
    }

    public function show($id)
    {
        $uatMeeting = UatMeeting::with(['meeting.customer', 'requirementMeeting', 'order'])->find($id);

        if (!$uatMeeting) {
            return response()->json(['error' => 'UAT meeting not found'], 404);
        }

        return response()->json($uatMeeting);
    }

    public function update(Request $request, $id)
    {
        $uatMeeting = UatMeeting::findOrFail($id);

        $validated = $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'requirement_id' => 'nullable|string|exists:meeting_details,requirement_id',
            'order_id' => 'nullable|string|exists:order,order_id',
            'budget_change' => 'nullable|string',
            'parts_changes' => 'nullable|string',
            'add_on_parts' => 'nullable|string',
            'parts_notes' => 'nullable|string',
            'case_size_change' => 'nullable|string|max:191',
            'overall_notes' => 'nullable|string',
            'quivicare_change' => 'nullable|in:no_change,add,remove,change_plan',
            'quivithread_change' => 'nullable|in:no_change,add,remove,change_option',
            'changes_required' => 'nullable|boolean',
            'new_proposal_required' => 'nullable|boolean',
            'customer_approval' => 'nullable|in:pending,approved,rejected',
            'follow_up_required' => 'nullable|boolean',
            'target_build_date' => 'nullable|date',
            'target_location' => 'nullable|string|max:191',
        ]);

        // uat_id is immutable after creation -- never accepted from the client.
        $uatMeeting->update($validated);

        return response()->json([
            'message' => 'UAT meeting updated successfully',
            'data' => $uatMeeting->fresh()->load(['meeting.customer', 'requirementMeeting', 'order']),
        ]);
    }

    public function destroy($id)
    {
        $uatMeeting = UatMeeting::findOrFail($id);
        $uatMeeting->delete();

        return response()->json([
            'message' => 'UAT meeting deleted successfully'
        ]);
    }
}
