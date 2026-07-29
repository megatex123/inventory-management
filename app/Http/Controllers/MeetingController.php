<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Customers;
use App\Support\BusinessId;
use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MeetingController extends Controller
{
    use FiltersSortsAndPaginates;

    public function index(Request $request)
    {
        $query = Meeting::with('customer');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('meetings.title', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('meetings.meeting_id', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('meetings.meeting_notes', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('meetings.meeting_date', 'LIKE', '%' . $escaped . '%')
                    ->orWhereHas('customer', function ($cq) use ($escaped) {
                        $cq->where('full_name', 'LIKE', '%' . $escaped . '%')
                            ->orWhere('phone', 'LIKE', '%' . $escaped . '%');
                    });
            });
        }

        $dateRange = $request->input('dateRange');
        if (is_scalar($dateRange) && $dateRange !== '') {
            $today = \Carbon\Carbon::today();
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('meeting_date', $today);
                    break;
                case 'yesterday':
                    $query->whereDate('meeting_date', $today->copy()->subDay());
                    break;
                case 'thisWeek':
                    $query->whereBetween('meeting_date', [
                        $today->copy()->startOfWeek(\Carbon\Carbon::SUNDAY)->toDateString(),
                        $today->toDateString(),
                    ]);
                    break;
                case 'lastWeek':
                    $startOfThisWeek = $today->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                    $query->whereBetween('meeting_date', [
                        $startOfThisWeek->copy()->subWeek()->toDateString(),
                        $startOfThisWeek->copy()->subDay()->toDateString(),
                    ]);
                    break;
                case 'thisMonth':
                    $query->whereYear('meeting_date', $today->year)
                        ->whereMonth('meeting_date', $today->month);
                    break;
                case 'lastMonth':
                    $lastMonth = $today->copy()->subMonthNoOverflow();
                    $query->whereYear('meeting_date', $lastMonth->year)
                        ->whereMonth('meeting_date', $lastMonth->month);
                    break;
                case 'thisYear':
                    $query->whereYear('meeting_date', $today->year);
                    break;
            }
        }

        // month and year are INDEPENDENT filters here (unlike other pages
        // in this initiative, where month requires year to be set) --
        // this matches the pre-migration client-side filteredMeetings
        // computed property's actual behavior, preserved as-is.
        $month = $request->input('month');
        if (is_scalar($month) && $month !== '') {
            $query->whereMonth('meeting_date', $month);
        }
        $year = $request->input('year');
        if (is_scalar($year) && $year !== '') {
            $query->whereYear('meeting_date', $year);
        }

        $hasDocument = $request->input('hasDocument');
        if (is_scalar($hasDocument) && $hasDocument !== '') {
            if ($hasDocument === 'yes') {
                $query->whereNotNull('document')->where('document', '!=', '');
            } elseif ($hasDocument === 'no') {
                $query->where(function ($q) {
                    $q->whereNull('document')->orWhere('document', '');
                });
            }
        }

        $this->resolveSortAndApply($query, $request, ['title', 'meeting_date', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request);
        $paginated = $query->paginate($perPage);

        return $this->paginatedResponse($paginated);
    }

    /**
     * All meetings, unpaginated, with the SAME query as the pre-migration
     * index() -- preserved byte-for-byte for 4 pre-existing bare-array
     * consumers (the meeting_details/uat_meeting create/edit "which
     * meeting" dropdowns).
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return response()->json(
            Meeting::with('customer')->latest()->get()
        );
    }

    /**
     * Whole-table statistics, unaffected by the list's active filters --
     * matches the pre-migration client-side calculateStatistics(), which
     * always ran over the full unfiltered dataset.
     *
     * @return \Illuminate\Http\Response
     */
    public function statistics()
    {
        $total = Meeting::count();

        $thisMonth = Meeting::whereMonth('meeting_date', now()->month)
            ->whereYear('meeting_date', now()->year)
            ->count();

        $withDocuments = Meeting::whereNotNull('document')
            ->where('document', '!=', '')
            ->count();

        $last7Days = Meeting::whereBetween('meeting_date', [
            now()->subDays(7)->toDateString(),
            now()->toDateString(),
        ])->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'thisMonth' => $thisMonth,
                'withDocuments' => $withDocuments,
                'last7Days' => $last7Days,
            ],
        ]);
    }

    /**
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $availableYears = Meeting::selectRaw('DISTINCT YEAR(meeting_date) as year')
            ->whereNotNull('meeting_date')
            ->orderByDesc('year')
            ->pluck('year');

        return response()->json([
            'success' => true,
            'data' => [
                'available_years' => $availableYears,
            ],
        ]);
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
