# UAT Meeting Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace `uat_meeting`'s old requirement-gathering fields (a duplicate of Requirement Meeting) with the new "Changes Requested / Build Details / Service Changes / UAT Result" structure the user specified, so a UAT Meeting records what changes a customer wants against an existing order, not original spec-gathering data.

**Architecture:** Backend: one idempotent migration replaces the column set on `uat_meeting`, `UatMeeting` model gets new `$fillable`/`$casts`/relations, `UatMeetingController` is rewritten for the new fields (server-generated `uat_id`), and `MeetingDetailsController` gains a lightweight `all()` endpoint (mirroring the existing `MeetingController::all()`) to populate the new "Require Meeting ID" dropdown. Frontend: `uat_meeting/create.vue`, `edit.vue`, `index.vue` are rebuilt around the new field set, reusing the dropdown-fetch pattern from `meeting_details/create.vue` and the "auto-generated on save" read-only ID pattern from `master_sku/create.vue`.

**Tech Stack:** Laravel 7 (PHP), Vue 2, MariaDB.

## Global Constraints

- Full replace, not additive — old `uat_meeting` columns are dropped, not kept alongside the new ones (confirmed zero real `uat_meeting` rows, no backfill needed).
- Service Changes fields (`quivicare_change`, `quivithread_change`) are **record-only** — no automatic side effects on `care_data`, `serve_data`, or any other table.
- `uat_id` (the UAT Meeting ID) is **always server-generated**, format `UAT-000001` via `BusinessId::next('uat_meeting', 'uat_id', 'UAT-', 6)` — never accepted from the request.
- No changes to `meeting_details` or `order` tables/models beyond the new `MeetingDetailsController::all()` endpoint.
- Migrations must use `Schema::hasColumn()` guards; column type changes (none needed here) would require raw `DB::statement()` per this project's Doctrine DBAL gotcha (`docs/QuiviTech/Dev-Setup.md`) — but plain `addColumn`/`dropColumn` via `Schema::table()` do **not** need DBAL and are used directly here, confirmed by `database/migrations/2026_08_08_000000_add_upgrade_pce_to_order_table.php`'s working `dropColumn()` calls.

---

### Task 1: Migration + `UatMeeting` model + `MeetingDetailsController::all()`

**Files:**
- Create: `database/migrations/2026_08_10_000000_redesign_uat_meeting_table.php`
- Modify: `app/Models/UatMeeting.php`
- Modify: `app/Http/Controllers/MeetingDetailsController.php`
- Modify: `routes/api.php:233-236` (meeting-details route block)

**Interfaces:**
- Produces: `uat_meeting` table with new columns (`requirement_id`, `order_id`, `budget_change`, `parts_changes`, `add_on_parts`, `parts_notes`, `case_size_change`, `overall_notes`, `quivicare_change`, `quivithread_change`, `changes_required`, `new_proposal_required`, `follow_up_required`, `customer_approval`) and old columns dropped.
- Produces: `UatMeeting::meeting()`, `UatMeeting::requirementMeeting()`, `UatMeeting::order()` relations for Task 2 to eager-load.
- Produces: `GET /api/meeting-details/all` returning `MeetingDetails::with('meeting.customer')->latest()->get()` as a JSON array, for Task 3's frontend dropdown.

- [ ] **Step 1: Confirm zero existing `uat_meeting` rows (safety check before dropping columns)**

Run:
```bash
flatpak-spawn --host docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan tinker --execute=\"echo App\\Models\\UatMeeting::count();\""
```
Expected: `0`. If not zero, STOP — the plan assumes no backfill is needed; escalate before proceeding.

- [ ] **Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RedesignUatMeetingTable extends Migration
{
    public function up()
    {
        Schema::table('uat_meeting', function (Blueprint $table) {
            if (!Schema::hasColumn('uat_meeting', 'requirement_id')) {
                $table->string('requirement_id')->nullable()->after('uat_id');
            }
            if (!Schema::hasColumn('uat_meeting', 'order_id')) {
                $table->string('order_id')->nullable()->after('requirement_id');
            }
            if (!Schema::hasColumn('uat_meeting', 'budget_change')) {
                $table->text('budget_change')->nullable()->after('order_id');
            }
            if (!Schema::hasColumn('uat_meeting', 'parts_changes')) {
                $table->text('parts_changes')->nullable()->after('budget_change');
            }
            if (!Schema::hasColumn('uat_meeting', 'add_on_parts')) {
                $table->text('add_on_parts')->nullable()->after('parts_changes');
            }
            if (!Schema::hasColumn('uat_meeting', 'parts_notes')) {
                $table->text('parts_notes')->nullable()->after('add_on_parts');
            }
            if (!Schema::hasColumn('uat_meeting', 'case_size_change')) {
                $table->string('case_size_change')->nullable()->after('parts_notes');
            }
            if (!Schema::hasColumn('uat_meeting', 'overall_notes')) {
                $table->text('overall_notes')->nullable()->after('case_size_change');
            }
            if (!Schema::hasColumn('uat_meeting', 'quivicare_change')) {
                $table->enum('quivicare_change', ['no_change', 'add', 'remove', 'change_plan'])
                    ->nullable()->after('overall_notes');
            }
            if (!Schema::hasColumn('uat_meeting', 'quivithread_change')) {
                $table->enum('quivithread_change', ['no_change', 'add', 'remove', 'change_option'])
                    ->nullable()->after('quivicare_change');
            }
            if (!Schema::hasColumn('uat_meeting', 'changes_required')) {
                $table->boolean('changes_required')->nullable()->after('quivithread_change');
            }
            if (!Schema::hasColumn('uat_meeting', 'new_proposal_required')) {
                $table->boolean('new_proposal_required')->nullable()->after('changes_required');
            }
            if (!Schema::hasColumn('uat_meeting', 'customer_approval')) {
                $table->enum('customer_approval', ['pending', 'approved', 'rejected'])
                    ->nullable()->after('new_proposal_required');
            }
            if (!Schema::hasColumn('uat_meeting', 'follow_up_required')) {
                $table->boolean('follow_up_required')->nullable()->after('customer_approval');
            }
        });

        Schema::table('uat_meeting', function (Blueprint $table) {
            foreach ([
                'initial_budget', 'reason', 'play_mode', 'include_monitor', 'include_notes',
                'notes', 'theme_style', 'preference', 'exemption', 'future_proof', 'case_size',
                'okay_with_aio', 'gpu_sag', 'need_rgb', 'qvcrf_tag', 'qvse', 'qvca', 'qvtd',
                'qvtd_notes',
            ] as $column) {
                if (Schema::hasColumn('uat_meeting', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down()
    {
        Schema::table('uat_meeting', function (Blueprint $table) {
            foreach ([
                'requirement_id', 'order_id', 'budget_change', 'parts_changes', 'add_on_parts',
                'parts_notes', 'case_size_change', 'overall_notes', 'quivicare_change',
                'quivithread_change', 'changes_required', 'new_proposal_required',
                'customer_approval', 'follow_up_required',
            ] as $column) {
                if (Schema::hasColumn('uat_meeting', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('uat_meeting', function (Blueprint $table) {
            if (!Schema::hasColumn('uat_meeting', 'initial_budget')) {
                $table->float('initial_budget')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'reason')) {
                $table->integer('reason')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'play_mode')) {
                $table->integer('play_mode')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'include_monitor')) {
                $table->string('include_monitor')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'include_notes')) {
                $table->text('include_notes')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'theme_style')) {
                $table->string('theme_style')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'preference')) {
                $table->string('preference')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'exemption')) {
                $table->string('exemption')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'future_proof')) {
                $table->boolean('future_proof')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'case_size')) {
                $table->integer('case_size')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'okay_with_aio')) {
                $table->boolean('okay_with_aio')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'gpu_sag')) {
                $table->boolean('gpu_sag')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'need_rgb')) {
                $table->boolean('need_rgb')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'qvcrf_tag')) {
                $table->boolean('qvcrf_tag')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'qvse')) {
                $table->boolean('qvse')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'qvca')) {
                $table->boolean('qvca')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'qvtd')) {
                $table->boolean('qvtd')->nullable();
            }
            if (!Schema::hasColumn('uat_meeting', 'qvtd_notes')) {
                $table->text('qvtd_notes')->nullable();
            }
        });
    }
}
```

- [ ] **Step 2b: Name the migration file correctly**

The class name is `RedesignUatMeetingTable` — Laravel resolves this from the filename `2026_08_10_000000_redesign_uat_meeting_table.php` automatically; no separate action needed, just confirm the filename matches (snake_case of the class name) before running.

- [ ] **Step 3: Run the migration**

```bash
flatpak-spawn --host docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan migrate --force"
```
Expected output includes: `Migrating: 2026_08_10_000000_redesign_uat_meeting_table` then `Migrated:  2026_08_10_000000_redesign_uat_meeting_table (... seconds)`.

- [ ] **Step 4: Verify the resulting schema**

```bash
flatpak-spawn --host docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan tinker --execute=\"print_r(Schema::getColumnListing('uat_meeting'));\""
```
Expected: array containing `id`, `meeting_id`, `uat_id`, `requirement_id`, `order_id`, `budget_change`, `parts_changes`, `add_on_parts`, `parts_notes`, `case_size_change`, `overall_notes`, `quivicare_change`, `quivithread_change`, `changes_required`, `new_proposal_required`, `customer_approval`, `follow_up_required`, `target_build_date`, `target_location`, `created_at`, `updated_at`, `deleted_at` — and NOT containing `initial_budget`, `reason`, `play_mode`, `include_monitor`, `include_notes`, `notes`, `theme_style`, `preference`, `exemption`, `future_proof`, `case_size`, `okay_with_aio`, `gpu_sag`, `need_rgb`, `qvcrf_tag`, `qvse`, `qvca`, `qvtd`, `qvtd_notes`.

- [ ] **Step 5: Rewrite `app/Models/UatMeeting.php`**

```php
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
```

- [ ] **Step 6: Add `MeetingDetailsController::all()`**

In `app/Http/Controllers/MeetingDetailsController.php`, add this method (place it right after `index()`, matching where `MeetingController::all()` sits relative to `MeetingController::index()`):

```php
    public function all()
    {
        return response()->json(
            MeetingDetails::with('meeting.customer')->latest()->get()
        );
    }
```

- [ ] **Step 7: Register the route BEFORE the `{id}` route**

In `routes/api.php`, the current meeting-details block (around line 233-236) reads:
```php
Route::get('/meeting-details', 'MeetingDetailsController@index');
Route::get('/meeting-details/statistics', 'MeetingDetailsController@statistics');
Route::get('/meeting-details/{id}', 'MeetingDetailsController@show');
```
Change it to:
```php
Route::get('/meeting-details', 'MeetingDetailsController@index');
Route::get('/meeting-details/all', 'MeetingDetailsController@all');
Route::get('/meeting-details/statistics', 'MeetingDetailsController@statistics');
Route::get('/meeting-details/{id}', 'MeetingDetailsController@show');
```
(`/all` must come before `/{id}` — otherwise Laravel routes `GET /meeting-details/all` to `show('all')`, which would 500 trying to `MeetingDetails::findOrFail('all')` or similar. This matches the existing `/meetings/all` vs `/meetings/{meeting}` ordering already in this same file.)

- [ ] **Step 8: Verify the model, relations, and new endpoint against real data**

```bash
flatpak-spawn --host docker exec quivitech-im-dev php -l /var/www/html/app/Models/UatMeeting.php
flatpak-spawn --host docker exec quivitech-im-dev php -l /var/www/html/app/Http/Controllers/MeetingDetailsController.php
```
Expected: `No syntax errors detected` for both.

```bash
flatpak-spawn --host curl -s "http://127.0.0.1/api/meeting-details/all"
```
Expected: `[]` (empty JSON array — confirmed zero `meeting_details` rows exist), HTTP 200, not a 404/500.

- [ ] **Step 9: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add database/migrations/2026_08_10_000000_redesign_uat_meeting_table.php app/Models/UatMeeting.php app/Http/Controllers/MeetingDetailsController.php routes/api.php
git commit -m "Redesign uat_meeting schema, add MeetingDetailsController::all()"
```

---

### Task 2: Rewrite `UatMeetingController`

**Files:**
- Modify: `app/Http/Controllers/UatMeetingController.php` (full rewrite of `index()`, `store()`, `update()`, `show()`)

**Interfaces:**
- Consumes: `UatMeeting::$fillable`/`$casts`/`meeting()`/`requirementMeeting()`/`order()` from Task 1.
- Consumes: `BusinessId::next(string $table, string $column, string $prefix, int $pad = 6): string` (existing, `app/Support/BusinessId.php`) — call as `BusinessId::next('uat_meeting', 'uat_id', 'UAT-', 6)`.
- Produces: `POST /api/uat-meeting`, `PUT /api/uat-meeting/{id}`, `GET /api/uat-meeting`, `GET /api/uat-meeting/{id}` behavior Task 3's frontend will call against.

- [ ] **Step 1: Rewrite the controller**

Replace the full contents of `app/Http/Controllers/UatMeetingController.php`:

```php
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
```

Note: the old `store()`/`update()` had two pre-existing bugs being fixed here as part of this rewrite (not separately, since the whole method is being replaced anyway): `BusinessId::next('uat_meetings', ...)` referenced a nonexistent plural table name (the real table is `uat_meeting`, singular — confirmed via `(new UatMeeting)->getTable()`), and `update()`'s validator had `'uat_id' => 'required|exists:uats,id'` referencing a nonexistent `uats` table. Both are gone now that `uat_id` is fully server-controlled.

- [ ] **Step 2: Syntax check**

```bash
flatpak-spawn --host docker exec quivitech-im-dev php -l /var/www/html/app/Http/Controllers/UatMeetingController.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Live-verify `store()` against real data — create disposable Meeting + MeetingDetails first (both tables are currently empty)**

```bash
flatpak-spawn --host docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan tinker --execute=\"
\\\$customerId = App\\Models\\Customers::first()->id;
echo 'customer_id='.\\\$customerId.PHP_EOL;

\\\$meetingId = App\\Support\\BusinessId::next('meetings', 'meeting_id', 'QV-CONS-', 6);
\\\$meeting = App\\Models\\Meeting::create([
    'meeting_id' => \\\$meetingId,
    'customer_id' => \\\$customerId,
    'title' => 'UAT verification test meeting',
    'meeting_date' => now(),
]);
echo 'meeting.id='.\\\$meeting->id.' meeting.meeting_id='.\\\$meeting->meeting_id.PHP_EOL;

\\\$requirementId = App\\Support\\BusinessId::next('meeting_details', 'requirement_id', 'QV-REQ-', 6);
\\\$md = App\\Models\\MeetingDetails::create([
    'meeting_id' => \\\$meeting->id,
    'requirement_id' => \\\$requirementId,
]);
echo 'meeting_details.requirement_id='.\\\$md->requirement_id.PHP_EOL;

echo 'order.order_id='.App\\Models\\Order::first()->order_id.PHP_EOL;
\""
```
Record the printed `meeting.meeting_id` numeric `id` (not the `QV-CONS-...` string), `meeting_details.requirement_id`, and `order.order_id` values — you'll need the meeting's numeric `id` for the `meeting_id` field below (it's a `belongsTo(Meeting::class)` on the numeric `id`, unlike `requirement_id`/`order_id` which use the business-ID string).

- [ ] **Step 4: POST a real UAT meeting**

Using the `meeting.id` (numeric), `requirement_id`, and `order_id` values from Step 3:
```bash
flatpak-spawn --host curl -s -X POST "http://127.0.0.1/api/uat-meeting" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{
    "meeting_id": <numeric meeting id from Step 3>,
    "requirement_id": "<requirement_id from Step 3>",
    "order_id": "QV-BLDP-000001",
    "budget_change": "Increase by RM500",
    "parts_changes": "Swap GPU to RTX 5070 Ti",
    "add_on_parts": "Extra case fan",
    "parts_notes": "Customer wants better airflow",
    "case_size_change": "ATX -> E-ATX",
    "overall_notes": "Customer happy with build so far",
    "quivicare_change": "change_plan",
    "quivithread_change": "no_change",
    "changes_required": true,
    "new_proposal_required": false,
    "customer_approval": "pending",
    "follow_up_required": true,
    "target_build_date": "2026-09-01",
    "target_location": "Studio A"
  }'
```
Expected: HTTP 201, JSON with `"uat_id": "UAT-000001"` (first-ever UAT meeting, since the table was confirmed empty in Task 1 Step 1), and all submitted fields echoed back correctly in `data`.

- [ ] **Step 5: Verify `show()` loads all three relations**

Using the `id` from Step 4's response:
```bash
flatpak-spawn --host curl -s "http://127.0.0.1/api/uat-meeting/<id from step 4>"
```
Expected: JSON containing `"meeting": {...}` (with nested `"customer": {...}`), `"requirement_meeting": {...}`, and `"order": {...}` — all three non-null, confirming the relations resolve correctly.

- [ ] **Step 6: Clean up all disposable test data from Steps 3-5**

```bash
flatpak-spawn --host docker exec quivitech-im-dev bash -c "cd /var/www/html && php artisan tinker --execute=\"
App\\Models\\UatMeeting::where('uat_id', 'UAT-000001')->forceDelete();
App\\Models\\MeetingDetails::where('requirement_id', 'LIKE', 'QV-REQ-%')->forceDelete();
App\\Models\\Meeting::where('meeting_id', 'LIKE', 'QV-CONS-%')->where('title', 'UAT verification test meeting')->forceDelete();
echo 'uat_meeting count: '.App\\Models\\UatMeeting::count().PHP_EOL;
echo 'meeting_details count: '.App\\Models\\MeetingDetails::count().PHP_EOL;
echo 'meeting count: '.App\\Models\\Meeting::count().PHP_EOL;
\""
```
Expected: all three counts print `0`, confirming zero leftover rows.

- [ ] **Step 7: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add app/Http/Controllers/UatMeetingController.php
git commit -m "Rewrite UatMeetingController for the new Changes Requested / UAT Result field set"
```

---

### Task 3: Rebuild frontend (create/edit/index)

**Files:**
- Modify: `resources/js/components/uat_meeting/create.vue` (full rewrite)
- Modify: `resources/js/components/uat_meeting/edit.vue` (full rewrite)
- Modify: `resources/js/components/uat_meeting/index.vue` (table columns + filters section only)

**Interfaces:**
- Consumes: `GET /api/meetings/all`, `GET /api/meeting-details/all` (Task 1), `GET /api/orders`, `POST /api/uat-meeting`, `PUT /api/uat-meeting/{id}`, `GET /api/uat-meeting/{id}`, `GET /api/uat-meeting` (Task 2) — all returning the new field shape from Task 2's controller.

- [ ] **Step 1: Rewrite `resources/js/components/uat_meeting/create.vue`**

```vue
<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Create UAT Meeting</h4>
          <router-link to="/uat-meeting" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <form @submit.prevent="submit">
          <h5 class="mb-3">Header</h5>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Meeting <span class="text-danger">*</span></label>
                <select v-model="form.meeting_id" class="form-control" required>
                  <option value="">Select Meeting</option>
                  <option v-for="m in meetings" :key="m.id" :value="m.id">{{ m.meeting_id }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Require Meeting ID</label>
                <select v-model="form.requirement_id" class="form-control">
                  <option value="">Select Require Meeting ID</option>
                  <option v-for="r in requirementMeetings" :key="r.id" :value="r.requirement_id">{{ r.requirement_id }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">QV-BLDP ID</label>
                <select v-model="form.order_id" class="form-control">
                  <option value="">Select Order</option>
                  <option v-for="o in orders" :key="o.id" :value="o.order_id">{{ o.order_id }}</option>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">UAT Meeting ID</label>
            <div class="form-control-plaintext bg-light p-2 rounded text-muted">
              Auto-generated on save (UAT-000001, ...)
            </div>
          </div>

          <hr>
          <h5 class="mb-3">Changes Requested</h5>
          <div class="form-group">
            <label class="form-label">Budget Change</label>
            <textarea v-model="form.budget_change" class="form-control" rows="2" placeholder="New budget / budget adjustment"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Parts Changes</label>
            <textarea v-model="form.parts_changes" class="form-control" rows="2" placeholder="Existing proposed parts customer wants changed"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Add-on Parts</label>
            <textarea v-model="form.add_on_parts" class="form-control" rows="2" placeholder="Additional parts requested"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Parts Notes</label>
            <textarea v-model="form.parts_notes" class="form-control" rows="2" placeholder="Reason/preferences regarding the changes"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Case Size Change</label>
            <input type="text" v-model="form.case_size_change" class="form-control" maxlength="191" placeholder="New case-size requirement">
          </div>
          <div class="form-group">
            <label class="form-label">Overall Notes</label>
            <textarea v-model="form.overall_notes" class="form-control" rows="2" placeholder="Anything else discussed"></textarea>
          </div>

          <hr>
          <h5 class="mb-3">Build Details</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Target Build Date</label>
                <input type="date" v-model="form.target_build_date" class="form-control">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Target Location</label>
                <input type="text" v-model="form.target_location" class="form-control" maxlength="191">
              </div>
            </div>
          </div>

          <hr>
          <h5 class="mb-3">Service Changes</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">QuiviCare</label>
                <select v-model="form.quivicare_change" class="form-control">
                  <option value="no_change">No Change</option>
                  <option value="add">Add</option>
                  <option value="remove">Remove</option>
                  <option value="change_plan">Change Plan</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">QuiviThread</label>
                <select v-model="form.quivithread_change" class="form-control">
                  <option value="no_change">No Change</option>
                  <option value="add">Add</option>
                  <option value="remove">Remove</option>
                  <option value="change_option">Change Option</option>
                </select>
              </div>
            </div>
          </div>

          <hr>
          <h5 class="mb-3">UAT Result</h5>
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">Changes Required</label>
                <select v-model="form.changes_required" class="form-control">
                  <option :value="null">-</option>
                  <option :value="true">Yes</option>
                  <option :value="false">No</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">New Proposal Required</label>
                <select v-model="form.new_proposal_required" class="form-control">
                  <option :value="null">-</option>
                  <option :value="true">Yes</option>
                  <option :value="false">No</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">Customer Approval</label>
                <select v-model="form.customer_approval" class="form-control">
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="rejected">Rejected</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">Follow-up Required</label>
                <select v-model="form.follow_up_required" class="form-control">
                  <option :value="null">-</option>
                  <option :value="true">Yes</option>
                  <option :value="false">No</option>
                </select>
              </div>
            </div>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Create UAT Meeting
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      meetings: [],
      requirementMeetings: [],
      orders: [],
      loading: false,
      errors: [],
      form: {
        meeting_id: '',
        requirement_id: '',
        order_id: '',
        budget_change: '',
        parts_changes: '',
        add_on_parts: '',
        parts_notes: '',
        case_size_change: '',
        overall_notes: '',
        quivicare_change: 'no_change',
        quivithread_change: 'no_change',
        changes_required: null,
        new_proposal_required: null,
        customer_approval: 'pending',
        follow_up_required: null,
        target_build_date: '',
        target_location: '',
      },
    };
  },
  mounted() {
    this.fetchMeetings();
    this.fetchRequirementMeetings();
    this.fetchOrders();
  },
  methods: {
    fetchMeetings() {
      axios.get('/api/meetings/all')
        .then(res => { this.meetings = res.data; })
        .catch(() => Swal.fire('Error!', 'Failed to load meetings', 'error'));
    },
    fetchRequirementMeetings() {
      axios.get('/api/meeting-details/all')
        .then(res => { this.requirementMeetings = res.data; })
        .catch(() => Swal.fire('Error!', 'Failed to load requirement meetings', 'error'));
    },
    fetchOrders() {
      axios.get('/api/orders')
        .then(res => { this.orders = res.data.data || res.data; })
        .catch(() => Swal.fire('Error!', 'Failed to load orders', 'error'));
    },
    submit() {
      this.loading = true;
      this.errors = [];

      const payload = { ...this.form };
      Object.keys(payload).forEach(key => {
        if (payload[key] === '') payload[key] = null;
      });

      axios.post('/api/uat-meeting', payload)
        .then(res => {
          Swal.fire({ title: 'Success!', text: `UAT Meeting ${res.data.data.uat_id} created successfully`, icon: 'success', timer: 2000, showConfirmButton: false })
            .then(() => this.$router.push('/uat-meeting'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to create UAT meeting');
          }
          Swal.fire('Error!', this.errors.join('<br>'), 'error');
        })
        .finally(() => { this.loading = false; });
    },
  },
};
</script>

<style scoped>
.form-card { border-radius: 10px; border: none; }
.form-label { font-weight: 600; color: #495057; }
</style>
```

- [ ] **Step 2: Rewrite `resources/js/components/uat_meeting/edit.vue`**

Same structure as `create.vue` above, with these differences:
- Header changes to `<h4 class="mb-0"><i class="fas fa-edit mr-2"></i>Edit UAT Meeting</h4>`.
- The "UAT Meeting ID" block becomes a real read-only value instead of the "auto-generated" placeholder:
  ```html
  <div class="form-group">
    <label class="form-label">UAT Meeting ID</label>
    <div class="form-control-plaintext bg-light p-2 rounded font-weight-bold">{{ form.uat_id }}</div>
  </div>
  ```
- `data()` adds `uatMeetingId: this.$route.params.id` (or read from `this.$route.params.id` directly in `mounted()`) and `form.uat_id: ''` (populated on load, never submitted — strip it from the payload before PUT, same as the empty-string-to-null pass).
- `mounted()` additionally calls a `fetchRecord()` method:
  ```js
  fetchRecord() {
    axios.get(`/api/uat-meeting/${this.$route.params.id}`)
      .then(res => {
        const data = res.data;
        this.form = {
          meeting_id: data.meeting_id,
          requirement_id: data.requirement_id || '',
          order_id: data.order_id || '',
          budget_change: data.budget_change || '',
          parts_changes: data.parts_changes || '',
          add_on_parts: data.add_on_parts || '',
          parts_notes: data.parts_notes || '',
          case_size_change: data.case_size_change || '',
          overall_notes: data.overall_notes || '',
          quivicare_change: data.quivicare_change || 'no_change',
          quivithread_change: data.quivithread_change || 'no_change',
          changes_required: data.changes_required,
          new_proposal_required: data.new_proposal_required,
          customer_approval: data.customer_approval || 'pending',
          follow_up_required: data.follow_up_required,
          target_build_date: data.target_build_date ? data.target_build_date.slice(0, 10) : '',
          target_location: data.target_location || '',
          uat_id: data.uat_id,
        };
      })
      .catch(() => Swal.fire('Error!', 'Failed to load UAT meeting', 'error'));
  },
  ```
- `submit()` becomes:
  ```js
  submit() {
    this.loading = true;
    this.errors = [];

    const payload = { ...this.form };
    delete payload.uat_id;
    Object.keys(payload).forEach(key => {
      if (payload[key] === '') payload[key] = null;
    });

    axios.put(`/api/uat-meeting/${this.$route.params.id}`, payload)
      .then(() => {
        Swal.fire({ title: 'Success!', text: 'UAT meeting updated successfully', icon: 'success', timer: 2000, showConfirmButton: false })
          .then(() => this.$router.push('/uat-meeting'));
      })
      .catch(error => {
        if (error.response && error.response.status === 422) {
          const validationErrors = error.response.data.errors;
          for (const field in validationErrors) {
            this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
          }
        } else {
          this.errors.push(error.response?.data?.message || 'Failed to update UAT meeting');
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      })
      .finally(() => { this.loading = false; });
  },
  ```

- [ ] **Step 3: Rewrite `resources/js/components/uat_meeting/index.vue`'s table**

Read the current file's `<table>` section and `<script>` `filters`/`filterColumns`/`items` rendering (same overall structure as `inv_care/index.vue`, already familiar from this session) and replace:
- Table headers: `#`, `UAT Meeting ID`, `Meeting`, `QV-BLDP ID`, `Customer Approval`, `Changes Required`, `Target Build Date`, `Actions`.
- Row cells:
  ```html
  <td class="align-middle font-weight-bold">{{ item.uat_id }}</td>
  <td class="align-middle">{{ item.meeting ? item.meeting.meeting_id : '-' }}</td>
  <td class="align-middle">{{ item.order_id || '-' }}</td>
  <td class="align-middle">
    <span :class="{
      'badge badge-warning': item.customer_approval === 'pending',
      'badge badge-success': item.customer_approval === 'approved',
      'badge badge-danger': item.customer_approval === 'rejected',
    }">{{ item.customer_approval || 'pending' }}</span>
  </td>
  <td class="align-middle text-center">
    <span :class="item.changes_required ? 'badge badge-info' : 'badge badge-secondary'">{{ item.changes_required ? 'Yes' : 'No' }}</span>
  </td>
  <td class="align-middle">{{ item.target_build_date ? item.target_build_date.slice(0, 10) : '-' }}</td>
  ```
- Filter columns (replace the `filterColumns` computed's array with):
  ```js
  filterColumns() {
    return [
      { key: 'search', label: 'UAT ID / Budget Change / Parts Changes / Notes', type: 'text' },
      { key: 'customerApproval', label: 'Customer Approval', type: 'select', options: [
        { value: 'pending', label: 'Pending' },
        { value: 'approved', label: 'Approved' },
        { value: 'rejected', label: 'Rejected' },
      ] },
      { key: 'changesRequired', label: 'Changes Required', type: 'select', options: [
        { value: '1', label: 'Yes' },
        { value: '0', label: 'No' },
      ] },
    ];
  },
  ```
  (Field names `customerApproval`/`changesRequired` match the query-param names `UatMeetingController::index()` reads via `applyEqualsFilter($query, $request, 'customerApproval', 'customer_approval')` etc. from Task 2.)
- Remove any remaining references to the dropped fields (`theme_style`, `preference`, `budgetRange`, `features`, `reason`, `caseSize`) anywhere else in the file — grep for them first:
  ```bash
  grep -n "theme_style\|preference\|budgetRange\|features\|reason\|caseSize\|exemption\|play_mode" resources/js/components/uat_meeting/index.vue
  ```
  Remove every match found.

- [ ] **Step 4: Confirm the frontend rebuilds cleanly**

```bash
flatpak-spawn --host tail -c 600 /tmp/npm-watch.log
```
Expected: `DONE  Compiled successfully in ...ms` with no error output above it. If `npm run watch` isn't currently running, start it per `docs/QuiviTech/Dev-Setup.md` and re-check.

- [ ] **Step 5: Live-verify the compiled bundle contains the new UAT meeting UI**

```bash
flatpak-spawn --host grep -c "UAT Meeting ID\|Changes Requested\|Service Changes" /home/penyahpepijat/claude/inventory-management/public/js/app.js
```
Expected: a non-zero count, confirming the new template strings made it into the compiled bundle (not a stale build).

- [ ] **Step 6: Commit**

```bash
cd /home/penyahpepijat/claude/inventory-management
git add resources/js/components/uat_meeting/create.vue resources/js/components/uat_meeting/edit.vue resources/js/components/uat_meeting/index.vue
git commit -m "Rebuild UAT Meeting create/edit/index UI for the new field set"
```

---

## Post-plan: vault documentation

After all three tasks are complete and reviewed, update `docs/QuiviTech/API-Routes.md` (new `GET /api/meeting-details/all` route, rewritten `uat-meeting` endpoints) and `docs/QuiviTech/Frontend-Components.md` (UAT Meeting's redesigned form) per this project's `CLAUDE.md` vault-maintenance rules, then commit and push.
