<?php

namespace App\Http\Controllers;

use App\Models\MeetingDetails;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;

class MeetingDetailsController extends Controller
{
    use FiltersSortsAndPaginates;

    public function index(Request $request)
    {
        $query = MeetingDetails::with('meeting.customer');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $keyword = strtolower((string) $search);

            $query->where(function ($q) use ($escaped, $keyword) {
                $q->where('theme_style', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('preference', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('exemption', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('target_location', 'LIKE', '%' . $escaped . '%')
                    ->orWhereHas('meeting', function ($mq) use ($escaped) {
                        $mq->where('meeting_id', 'LIKE', '%' . $escaped . '%');
                    })
                    ->orWhere('requirement_id', 'LIKE', '%' . $escaped . '%');

                // Replicates the pre-migration client-side search's
                // "does the keyword appear as a substring of the fixed
                // display word" behavior for the two enum-derived text
                // columns (e.g. typing "gam" matches reason=2 because
                // "gam" is a substring of "gaming").
                if ($keyword !== '' && strpos('work', $keyword) !== false) {
                    $q->orWhere('reason', 1);
                }
                if ($keyword !== '' && strpos('gaming', $keyword) !== false) {
                    $q->orWhere('reason', 2);
                }
                // The old JS only tested play_mode text when reason==2
                // (play mode is only shown/meaningful for Gaming rows) --
                // replicate that guard so a Work row with a stray
                // play_mode value can't match via this path.
                if ($keyword !== '' && strpos('multiplayer', $keyword) !== false) {
                    $q->orWhere(function ($sq) {
                        $sq->where('reason', 2)->where('play_mode', 1);
                    });
                }
                if ($keyword !== '' && strpos('singleplayer', $keyword) !== false) {
                    $q->orWhere(function ($sq) {
                        $sq->where('reason', 2)->where('play_mode', 2);
                    });
                }
            });
        }

        $this->applyEqualsFilter($query, $request, 'reason', 'reason');
        $this->applyEqualsFilter($query, $request, 'caseSize', 'case_size');

        $budgetRange = $request->input('budgetRange');
        if (is_scalar($budgetRange) && $budgetRange !== '') {
            if ($budgetRange === 'low') {
                $query->whereRaw('COALESCE(initial_budget, 0) < 7000');
            } elseif ($budgetRange === 'medium') {
                $query->whereRaw('COALESCE(initial_budget, 0) >= 7000 AND COALESCE(initial_budget, 0) <= 10000');
            } elseif ($budgetRange === 'high') {
                $query->whereRaw('COALESCE(initial_budget, 0) > 10000');
            }
        }

        $features = $request->input('features');
        if (is_scalar($features) && $features !== '') {
            if ($features === 'future_proof') {
                $query->where('future_proof', 1);
            } elseif ($features === 'aio') {
                $query->where('okay_with_aio', 1);
            } elseif ($features === 'gpu_sag') {
                $query->where('gpu_sag', 1);
            } elseif ($features === 'rgb') {
                $query->where('need_rgb', 1);
            }
        }

        $this->resolveSortAndApply($query, $request, ['initial_budget', 'target_build_date', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request);
        $paginated = $query->paginate($perPage);

        return $this->paginatedResponse($paginated);
    }

    /**
     * Whole-table statistics, unaffected by the list's active filters --
     * matches the pre-migration client-side calculateStatistics().
     *
     * @return \Illuminate\Http\Response
     */
    public function statistics()
    {
        $total = MeetingDetails::count();
        $gaming = MeetingDetails::where('reason', 2)->count();
        $work = MeetingDetails::where('reason', 1)->count();

        $avgBudget = MeetingDetails::where('initial_budget', '>', 0)
            ->whereNotNull('initial_budget')
            ->avg('initial_budget');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'gaming' => $gaming,
                'work' => $work,
                'avgBudget' => $avgBudget ? round($avgBudget) : 0,
            ],
        ]);
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

        $validated['requirement_id'] = BusinessId::next('meeting_details', 'requirement_id', 'CONS-RQD-', 6);

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
