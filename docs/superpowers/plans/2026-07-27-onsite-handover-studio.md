# OnSite Handover (Studio) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship the OnSite Handover (Studio) QC report — a lighter, 7-section report a technician fills out when delivering/handing over a PC that was built entirely in-studio (no on-site assembly, unlike its sibling OnSite Handover (QuiviCraft)).

**Architecture:** Reuses OnSite Handover (QuiviCraft)'s exact architecture: one new table `onsite_handovers_studio` (single wide table, no child tables), 7 section-scoped save endpoints each writing a disjoint column subset of the same row, the existing shared `PhotoUploadField.vue` component (already built, no new shared component needed), and the same auto-populated read-only cross-report/tier lookup pattern.

**Tech Stack:** Laravel 7 (PHP), Eloquent, Vue 2 Options API, Bootstrap 4, axios, SweetAlert2.

## Global Constraints

- No automated test suite exists in this codebase. Implementer/reviewer subagents have no Docker/PHP/Node access in this environment — self-checks are manual brace-balance/read-through, not `php -l`/`npm run watch`. The controller runs the real migration and curls every endpoint live after each task.
- **One column, one writer.** `arrival_time` is written ONLY by `updateArrival()` — `updateReportInfo()`'s field list must NOT include it, even though `show()` displays the row's current value everywhere it's asked for. Same rule, same reasoning, as OnSite Handover (QuiviCraft) — this codebase has hit this exact bug class multiple times when it wasn't enforced deliberately from the start.
- Every new Vue section component follows the exact shape established by OnSite Handover (QuiviCraft)'s sections: `apiBase`/`initialData` props, `FIELD_KEYS`/`BOOLEAN_KEYS`/`STRING_KEYS` constants, `buildForm()` in `data()` + `watch: { initialData }`, `save()` posting only its own `FIELD_KEYS` and emitting `saved`. Photo-bearing sections track `newXPhotos`/`removeXPhotos` and submit via `FormData`/`multipart/form-data`, reusing the existing `resources/js/components/shared/PhotoUploadField.vue` (already built for the QuiviCraft version — do not create a new photo widget).
- **`index.vue` must use `Object.assign(this.onsiteHandoverStudio, data)` in every `@saved` handler, NOT a full reassignment (`this.onsiteHandoverStudio = data`).** OnSite Handover (QuiviCraft)'s final review found and fixed exactly this bug: since every section shares the SAME `initial-data` object reference (single table, not per-section sub-keys like Performance Testing), a full reassignment creates a new object reference that spuriously fires every OTHER open section's `watch: { initialData }`, silently wiping unsaved in-progress edits. Build the `Object.assign` form in from the start this time — there is no budgeted fix-up task for this.
- Every controller update method follows the shape already proven in `OnsiteHandoverController` (the QuiviCraft version, read it for comparison): find-or-404 by `(order_id, round)`, validate, `fill($request->only(self::X_FIELDS))`, merge photos if the section has any, save, return `['success' => true, 'message' => ..., 'data' => $handover->fresh()]`.
- No `complete()` action — `status` is a normal field on `updateReportInfo()`, same as the QuiviCraft version.
- Photo fields (`arrival_photos`, `post_transport_photos`, `post_handover_photos`) are optional, multi-file, no required-count validation. Reuse the `mergePhotos()`/`storePhotos()` pattern (own private copies in this new controller, storage subdirectory `'onsite-handovers-studio'` — distinct from the QuiviCraft version's `'onsite-handovers'` folder).
- MySQL 64-char constraint-name limit: table name `onsite_handovers_studio` (23 chars) keeps every default constraint name (`onsite_handovers_studio_order_id_round_unique` = 45 chars, `onsite_handovers_studio_order_id_foreign` = 40 chars) safely under 64. No custom short constraint names needed.
- Spec source of truth: `docs/superpowers/specs/2026-07-27-onsite-handover-studio-design.md`. Column names, types, and section boundaries in this plan are transcribed directly from it — do not invent alternate names.

---

### Task 1: Migration + Model

**Files:**
- Create: `database/migrations/2026_07_28_100000_create_onsite_handovers_studio_table.php`
- Create: `app/Models/OnsiteHandoverStudio.php`

**Interfaces:**
- Produces: the `onsite_handovers_studio` table and `OnsiteHandoverStudio` model — every later task depends on these exact column names.

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnsiteHandoversStudioTable extends Migration
{
    public function up()
    {
        Schema::create('onsite_handovers_studio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('round')->default(1);
            $table->string('report_id')->unique();
            $table->string('report_version')->nullable();
            $table->string('status')->default('in_progress');

            // Report Information
            $table->date('service_date')->nullable();
            $table->time('arrival_time')->nullable();
            $table->time('handover_completion_time')->nullable();
            $table->string('technician_name')->nullable();
            $table->string('assistant_technician')->nullable();
            $table->string('service_location')->nullable();
            $table->string('service_type')->nullable();

            // Build Information
            $table->string('operating_system')->nullable();
            $table->string('operating_system_version')->nullable();

            // Studio Documentation Verification
            $table->boolean('security_seal_verified_before_delivery')->nullable();
            $table->text('studio_docs_notes')->nullable();

            // On-Site Arrival Verification
            $table->boolean('workspace_available')->nullable();
            $table->boolean('power_outlet_available')->nullable();
            $table->boolean('display_available')->nullable();
            $table->boolean('keyboard_available')->nullable();
            $table->boolean('mouse_available')->nullable();
            $table->boolean('internet_available')->nullable();
            $table->json('arrival_photos')->nullable();
            $table->text('arrival_notes')->nullable();

            // Post-Transport Hardware Verification
            $table->boolean('gpu_securely_installed')->nullable();
            $table->boolean('memory_fully_seated')->nullable();
            $table->boolean('cpu_cooler_secure')->nullable();
            $table->boolean('power_connections_secure')->nullable();
            $table->boolean('storage_secure')->nullable();
            $table->boolean('no_loose_cables')->nullable();
            $table->boolean('no_loose_screws')->nullable();
            $table->json('post_transport_photos')->nullable();
            $table->text('post_transport_notes')->nullable();

            // Post-Handover System Verification
            $table->boolean('system_powered_on')->nullable();
            $table->boolean('post_successful')->nullable();
            $table->boolean('windows_boot_successful')->nullable();
            $table->boolean('display_output_verified')->nullable();
            $table->boolean('network_connected')->nullable();
            $table->boolean('internet_accessible')->nullable();
            $table->boolean('audio_verified')->nullable();
            $table->boolean('usb_ports_verified')->nullable();
            $table->boolean('rgb_lighting_verified')->nullable();
            $table->json('post_handover_photos')->nullable();
            $table->text('post_handover_notes')->nullable();

            // Customer Acceptance
            $table->boolean('physical_condition_accepted')->nullable();
            $table->boolean('system_boot_verified')->nullable();
            $table->boolean('display_verified')->nullable();
            $table->boolean('accessories_received')->nullable();
            $table->boolean('documentation_received')->nullable();
            $table->boolean('customer_demonstration_completed')->nullable();
            $table->boolean('customer_questions_addressed')->nullable();
            $table->text('customer_acceptance_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['order_id', 'round']);
            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('onsite_handovers_studio');
    }
}
```

Note: the orders table is named `order` (singular) in this codebase's DB, matching `app/Models/Order.php`'s `protected $table = 'order';`.

- [ ] **Step 2: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnsiteHandoverStudio extends Model
{
    use SoftDeletes;

    protected $table = 'onsite_handovers_studio';
    protected $guarded = ['id'];

    protected $casts = [
        'order_id' => 'integer',
        'round' => 'integer',
        'service_date' => 'date',
        'security_seal_verified_before_delivery' => 'boolean',
        'workspace_available' => 'boolean',
        'power_outlet_available' => 'boolean',
        'display_available' => 'boolean',
        'keyboard_available' => 'boolean',
        'mouse_available' => 'boolean',
        'internet_available' => 'boolean',
        'arrival_photos' => 'array',
        'gpu_securely_installed' => 'boolean',
        'memory_fully_seated' => 'boolean',
        'cpu_cooler_secure' => 'boolean',
        'power_connections_secure' => 'boolean',
        'storage_secure' => 'boolean',
        'no_loose_cables' => 'boolean',
        'no_loose_screws' => 'boolean',
        'post_transport_photos' => 'array',
        'system_powered_on' => 'boolean',
        'post_successful' => 'boolean',
        'windows_boot_successful' => 'boolean',
        'display_output_verified' => 'boolean',
        'network_connected' => 'boolean',
        'internet_accessible' => 'boolean',
        'audio_verified' => 'boolean',
        'usb_ports_verified' => 'boolean',
        'rgb_lighting_verified' => 'boolean',
        'post_handover_photos' => 'array',
        'physical_condition_accepted' => 'boolean',
        'system_boot_verified' => 'boolean',
        'display_verified' => 'boolean',
        'accessories_received' => 'boolean',
        'documentation_received' => 'boolean',
        'customer_demonstration_completed' => 'boolean',
        'customer_questions_addressed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
```

- [ ] **Step 3: Lint both files**

Run: `php -l database/migrations/2026_07_28_100000_create_onsite_handovers_studio_table.php` and `php -l app/Models/OnsiteHandoverStudio.php`. Expected: `No syntax errors detected` for each. If unavailable, manually verify brace balance.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_07_28_100000_create_onsite_handovers_studio_table.php app/Models/OnsiteHandoverStudio.php
git commit -m "Add onsite_handovers_studio migration and model"
```

---

### Task 2: Controller Part 1 — show(), report ID gen, 4 simple sections

**Files:**
- Create: `app/Http/Controllers/OnsiteHandoverStudioController.php`

**Interfaces:**
- Consumes: `OnsiteHandoverStudio` model (Task 1); `Order`, `CraftInspection`, `PerformanceTest`, `ServeData`, `CareData` models (already exist).
- Produces: `show()`, `updateReportInfo()`, `updateBuildInfo()`, `updateStudioDocs()`, `updateCustomerAcceptance()` — Task 4's routes point to these by exact name. This task creates the file; Task 3 adds the 3 photo-bearing methods to the same file.

- [ ] **Step 1: Write the controller with field constants, show(), and the report-ID generator**

```php
<?php

namespace App\Http\Controllers;

use App\Models\CareData;
use App\Models\CraftInspection;
use App\Models\OnsiteHandoverStudio;
use App\Models\Order;
use App\Models\PerformanceTest;
use App\Models\ServeData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OnsiteHandoverStudioController extends Controller
{
    const REPORT_INFO_FIELDS = [
        'report_version', 'service_date', 'handover_completion_time',
        'technician_name', 'assistant_technician', 'service_location', 'service_type', 'status',
    ];

    const BUILD_INFO_FIELDS = [
        'operating_system', 'operating_system_version',
    ];

    const STUDIO_DOCS_FIELDS = [
        'security_seal_verified_before_delivery', 'studio_docs_notes',
    ];

    const CUSTOMER_ACCEPTANCE_FIELDS = [
        'physical_condition_accepted', 'system_boot_verified', 'display_verified', 'accessories_received',
        'documentation_received', 'customer_demonstration_completed', 'customer_questions_addressed',
        'customer_acceptance_notes',
    ];

    public function show($orderId, $round = 1)
    {
        $order = Order::with(['customer', 'craft'])->find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            $handover = OnsiteHandoverStudio::create([
                'order_id' => $orderId,
                'round' => $round,
                'report_id' => $this->generateReportId(),
                'status' => 'in_progress',
            ]);
        }

        $craftInspection = CraftInspection::where('order_id', $orderId)->orderByDesc('round')->first();
        $performanceTest = PerformanceTest::where('order_id', $orderId)->orderByDesc('round')->first();
        $serveData = ServeData::where('order_id', $orderId)->with('serve')->orderByDesc('id')->first();
        $careData = CareData::where('order_id', $orderId)->with('care')->orderByDesc('id')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'onsite_handover_studio' => $handover,
                'studio_inspection_report_completed' => $craftInspection ? $craftInspection->status === 'completed' : false,
                'studio_inspection_report_id' => $craftInspection ? $craftInspection->id : null,
                'performance_testing_report_completed' => $performanceTest ? $performanceTest->status === 'completed' : false,
                'performance_testing_report_id' => $performanceTest ? $performanceTest->id : null,
                'quivicraft_id' => $order->order_id,
                'quivicraft_plan' => optional($order->craft)->name,
                'quiviserve_id' => $serveData ? $serveData->serve_id : null,
                'quiviserve_customer_id' => optional($order->customer)->customer_id,
                'quiviserve_plan' => $serveData ? optional($serveData->serve)->name : null,
                'quivicare_id' => $careData ? $careData->care_id : null,
                'quivicare_plan' => $careData ? optional($careData->care)->name : null,
            ],
        ]);
    }

    private function generateReportId()
    {
        $nextId = OnsiteHandoverStudio::count() + 1;

        return 'OSH-STD-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
```

- [ ] **Step 2: Add `updateReportInfo()`**

```php
    public function updateReportInfo(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'report_version' => 'nullable|string|max:255',
            'service_date' => 'nullable|date',
            'handover_completion_time' => 'nullable|string|max:255',
            'technician_name' => 'nullable|string|max:255',
            'assistant_technician' => 'nullable|string|max:255',
            'service_location' => 'nullable|string|max:255',
            'service_type' => 'nullable|in:full_onsite_assembly,full_onsite_assembly_tag_along,studio_assembly',
            'status' => 'nullable|in:in_progress,completed,deferred,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::REPORT_INFO_FIELDS));
            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Report information updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update report information', 'error' => $e->getMessage()], 500);
        }
    }
```

Add this method directly after `generateReportId()`.

- [ ] **Step 3: Add `updateBuildInfo()`**

```php
    public function updateBuildInfo(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'operating_system' => 'nullable|string|max:255',
            'operating_system_version' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::BUILD_INFO_FIELDS));
            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Build information updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update build information', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 4: Add `updateStudioDocs()`**

```php
    public function updateStudioDocs(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'security_seal_verified_before_delivery' => 'nullable|boolean',
            'studio_docs_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::STUDIO_DOCS_FIELDS));
            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Studio documentation verification updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update studio documentation verification', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 5: Add `updateCustomerAcceptance()`**

```php
    public function updateCustomerAcceptance(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'physical_condition_accepted' => 'nullable|boolean',
            'system_boot_verified' => 'nullable|boolean',
            'display_verified' => 'nullable|boolean',
            'accessories_received' => 'nullable|boolean',
            'documentation_received' => 'nullable|boolean',
            'customer_demonstration_completed' => 'nullable|boolean',
            'customer_questions_addressed' => 'nullable|boolean',
            'customer_acceptance_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::CUSTOMER_ACCEPTANCE_FIELDS));
            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Customer acceptance updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update customer acceptance', 'error' => $e->getMessage()], 500);
        }
    }
```

Add this method, then close the class with `}` on its own line after it.

- [ ] **Step 6: Lint the file**

Run: `php -l app/Http/Controllers/OnsiteHandoverStudioController.php`. Expected: `No syntax errors detected`.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/OnsiteHandoverStudioController.php
git commit -m "Add OnsiteHandoverStudioController: show(), report ID generation, Report/Build Info, Studio Docs, Customer Acceptance"
```

---

### Task 3: Controller Part 2 — Arrival, Post-Transport, Post-Handover sections (photo handling)

**Files:**
- Modify: `app/Http/Controllers/OnsiteHandoverStudioController.php`

**Interfaces:**
- Produces: `updateArrival()`, `updatePostTransport()`, `updatePostHandover()`, plus private `storePhotos()`/`mergePhotos()` helpers — Task 4's routes point to the 3 public methods by exact name.

- [ ] **Step 1: Add the 3 field constants**

Add directly after `CUSTOMER_ACCEPTANCE_FIELDS`:

```php
    const ARRIVAL_FIELDS = [
        'arrival_time', 'workspace_available', 'power_outlet_available', 'display_available',
        'keyboard_available', 'mouse_available', 'internet_available', 'arrival_notes',
    ];

    const POST_TRANSPORT_FIELDS = [
        'gpu_securely_installed', 'memory_fully_seated', 'cpu_cooler_secure', 'power_connections_secure',
        'storage_secure', 'no_loose_cables', 'no_loose_screws', 'post_transport_notes',
    ];

    const POST_HANDOVER_FIELDS = [
        'system_powered_on', 'post_successful', 'windows_boot_successful', 'display_output_verified',
        'network_connected', 'internet_accessible', 'audio_verified', 'usb_ports_verified',
        'rgb_lighting_verified', 'post_handover_notes',
    ];
```

Note: `arrival_time` appears here and NOT in `REPORT_INFO_FIELDS` (Task 2) — deliberate, see Global Constraints' "one column, one writer" rule. Double check `REPORT_INFO_FIELDS` still excludes it before proceeding.

- [ ] **Step 2: Add `updateArrival()`**

Add after `updateCustomerAcceptance()`:

```php
    public function updateArrival(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'arrival_time' => 'nullable|string|max:255',
            'workspace_available' => 'nullable|boolean',
            'power_outlet_available' => 'nullable|boolean',
            'display_available' => 'nullable|boolean',
            'keyboard_available' => 'nullable|boolean',
            'mouse_available' => 'nullable|boolean',
            'internet_available' => 'nullable|boolean',
            'arrival_notes' => 'nullable|string|max:1000',
            'arrival_photos.*' => 'nullable|image|max:5120',
            'remove_arrival_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::ARRIVAL_FIELDS));

            if ($request->hasFile('arrival_photos') || $request->filled('remove_arrival_photos')) {
                $handover->arrival_photos = $this->mergePhotos($handover->arrival_photos, $request, 'arrival_photos', 'remove_arrival_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Arrival verification updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update arrival verification', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 3: Add `updatePostTransport()`**

```php
    public function updatePostTransport(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'gpu_securely_installed' => 'nullable|boolean',
            'memory_fully_seated' => 'nullable|boolean',
            'cpu_cooler_secure' => 'nullable|boolean',
            'power_connections_secure' => 'nullable|boolean',
            'storage_secure' => 'nullable|boolean',
            'no_loose_cables' => 'nullable|boolean',
            'no_loose_screws' => 'nullable|boolean',
            'post_transport_notes' => 'nullable|string|max:1000',
            'post_transport_photos.*' => 'nullable|image|max:5120',
            'remove_post_transport_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::POST_TRANSPORT_FIELDS));

            if ($request->hasFile('post_transport_photos') || $request->filled('remove_post_transport_photos')) {
                $handover->post_transport_photos = $this->mergePhotos($handover->post_transport_photos, $request, 'post_transport_photos', 'remove_post_transport_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Post-transport hardware verification updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update post-transport hardware verification', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 4: Add `updatePostHandover()`**

```php
    public function updatePostHandover(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'system_powered_on' => 'nullable|boolean',
            'post_successful' => 'nullable|boolean',
            'windows_boot_successful' => 'nullable|boolean',
            'display_output_verified' => 'nullable|boolean',
            'network_connected' => 'nullable|boolean',
            'internet_accessible' => 'nullable|boolean',
            'audio_verified' => 'nullable|boolean',
            'usb_ports_verified' => 'nullable|boolean',
            'rgb_lighting_verified' => 'nullable|boolean',
            'post_handover_notes' => 'nullable|string|max:1000',
            'post_handover_photos.*' => 'nullable|image|max:5120',
            'remove_post_handover_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::POST_HANDOVER_FIELDS));

            if ($request->hasFile('post_handover_photos') || $request->filled('remove_post_handover_photos')) {
                $handover->post_handover_photos = $this->mergePhotos($handover->post_handover_photos, $request, 'post_handover_photos', 'remove_post_handover_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Post-handover system verification updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update post-handover system verification', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 5: Add the private photo helpers**

Add these two methods just before the class's final closing `}`:

```php
    private function storePhotos(Request $request, $field)
    {
        if (!$request->hasFile($field)) {
            return [];
        }

        $paths = [];
        foreach ($request->file($field) as $file) {
            $paths[] = $file->store('onsite-handovers-studio', 'public');
        }

        return $paths;
    }

    private function mergePhotos($existing, Request $request, $field, $removeField)
    {
        $existing = $existing ?? [];
        $toRemove = $request->input($removeField, []);

        foreach ($toRemove as $path) {
            if (($key = array_search($path, $existing)) !== false) {
                Storage::disk('public')->delete($path);
                unset($existing[$key]);
            }
        }

        $existing = array_values($existing);
        $newPhotos = $this->storePhotos($request, $field);

        return array_merge($existing, $newPhotos);
    }
```

Note the storage subdirectory is `'onsite-handovers-studio'`, distinct from the QuiviCraft version's `'onsite-handovers'`.

- [ ] **Step 6: Lint the file**

Run: `php -l app/Http/Controllers/OnsiteHandoverStudioController.php`. Expected: `No syntax errors detected`. Also do a full read-through confirming all 7 public section-update methods plus `show()` plus the 2 private helpers exist exactly once each (no duplicates from copy/paste).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/OnsiteHandoverStudioController.php
git commit -m "Add Arrival, Post-Transport, and Post-Handover sections with photo upload support"
```

---

### Task 4: Routes (backend API + frontend router)

**Files:**
- Modify: `routes/api.php`
- Modify: `resources/js/routes.js`

**Interfaces:**
- Consumes: all 8 `OnsiteHandoverStudioController` methods (Tasks 2-3) by exact name.
- Produces: the `order/{orderId}/onsite-handover-studio/{round}` API prefix and the `onsitehandoverstudio` frontend route name — Task 7's `index.vue` and Task 8's quick-launch button both depend on this route name.

- [ ] **Step 1: Add the API route group**

In `routes/api.php`, find the closing of the OnSite Handover (QuiviCraft) route group:

```php
Route::prefix('order/{orderId}/onsite-handover/{round}')->group(function () {
    Route::get('/', 'OnsiteHandoverController@show');
    Route::post('/report-info', 'OnsiteHandoverController@updateReportInfo');
    Route::post('/customer-info', 'OnsiteHandoverController@updateCustomerInfo');
    Route::post('/build-info', 'OnsiteHandoverController@updateBuildInfo');
    Route::post('/studio-docs', 'OnsiteHandoverController@updateStudioDocs');
    Route::post('/arrival', 'OnsiteHandoverController@updateArrival');
    Route::post('/transportation', 'OnsiteHandoverController@updateTransportation');
    Route::post('/assembly', 'OnsiteHandoverController@updateAssembly');
    Route::post('/post-build-hardware', 'OnsiteHandoverController@updatePostBuildHardware');
    Route::post('/post-build-software', 'OnsiteHandoverController@updatePostBuildSoftware');
    Route::post('/customer-acceptance', 'OnsiteHandoverController@updateCustomerAcceptance');
    Route::post('/acknowledgement', 'OnsiteHandoverController@updateAcknowledgement');
});
```

Add immediately after this block's closing `});`:

```php

/*
|--------------------------------------------------------------------------
| ONSITE HANDOVER (Studio)
|--------------------------------------------------------------------------
*/
Route::prefix('order/{orderId}/onsite-handover-studio/{round}')->group(function () {
    Route::get('/', 'OnsiteHandoverStudioController@show');
    Route::post('/report-info', 'OnsiteHandoverStudioController@updateReportInfo');
    Route::post('/build-info', 'OnsiteHandoverStudioController@updateBuildInfo');
    Route::post('/studio-docs', 'OnsiteHandoverStudioController@updateStudioDocs');
    Route::post('/arrival', 'OnsiteHandoverStudioController@updateArrival');
    Route::post('/post-transport', 'OnsiteHandoverStudioController@updatePostTransport');
    Route::post('/post-handover', 'OnsiteHandoverStudioController@updatePostHandover');
    Route::post('/customer-acceptance', 'OnsiteHandoverStudioController@updateCustomerAcceptance');
});
```

- [ ] **Step 2: Register the frontend route**

In `resources/js/routes.js`, find:

```js
let onsitehandover = require('./components/onsite_handover/index.vue').default;
```

Replace with:

```js
let onsitehandover = require('./components/onsite_handover/index.vue').default;
let onsitehandoverstudio = require('./components/onsite_handover_studio/index.vue').default;
```

Then find:

```js
      { path: '/order/:id/onsite-handover/:round', component: onsitehandover, name: 'onsitehandover', meta: { layout: 'app' } },
```

Replace with:

```js
      { path: '/order/:id/onsite-handover/:round', component: onsitehandover, name: 'onsitehandover', meta: { layout: 'app' } },
      { path: '/order/:id/onsite-handover-studio/:round', component: onsitehandoverstudio, name: 'onsitehandoverstudio', meta: { layout: 'app' } },
```

Note: `resources/js/components/onsite_handover_studio/index.vue` does not exist yet — it's created in Task 7. This is expected; earlier tasks in this plan don't touch the frontend build, so there's no broken intermediate compile state.

- [ ] **Step 3: Lint both files**

Run: `php -l routes/api.php`. For `routes.js`, do a manual read-through confirming well-formed syntax.

- [ ] **Step 4: Commit**

```bash
git add routes/api.php resources/js/routes.js
git commit -m "Wire OnSite Handover (Studio) routes (backend API + frontend router)"
```

---

### Task 5: Frontend — Report Info, Build Info, Studio Docs, Customer Acceptance sections

**Files:**
- Create: `resources/js/components/onsite_handover_studio/ReportInfoSection.vue`
- Create: `resources/js/components/onsite_handover_studio/BuildInfoSection.vue`
- Create: `resources/js/components/onsite_handover_studio/StudioDocsSection.vue`
- Create: `resources/js/components/onsite_handover_studio/CustomerAcceptanceSection.vue`

**Interfaces:**
- Consumes: `POST {apiBase}/report-info`, `.../build-info`, `.../studio-docs`, `.../customer-acceptance` (all from Task 2).
- Produces: each emits `saved` with the response `data` — Task 7's `index.vue` wiring depends on this.
- `BuildInfoSection` additionally takes read-only display props `quivicraftId`/`quivicraftPlan`/`quiviserveId`/`quiviserveCustomerId`/`quiviservePlan`/`quivicareId`/`quivicarePlan` (String, nullable, default `null`) — NOT part of its `FIELD_KEYS`/`save()`, purely informational. Same shape as OnSite Handover (QuiviCraft)'s `BuildInfoSection.vue` — read that file for the exact read-only-props display pattern to copy.
- `StudioDocsSection` additionally takes read-only display props `studioInspectionReportCompleted`/`studioInspectionReportId`/`performanceTestingReportCompleted`/`performanceTestingReportId` (Boolean/Number, nullable) — same rule, display-only.

- [ ] **Step 1: Write `ReportInfoSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Report Information</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Report Version</label>
            <input type="text" class="form-control" v-model="form.report_version">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Service Date</label>
            <input type="date" class="form-control" v-model="form.service_date">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Handover Completion Time</label>
            <input type="time" class="form-control" v-model="form.handover_completion_time">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Report Status</label>
            <select class="form-control" v-model="form.status">
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="deferred">Deferred</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Technician Name</label>
            <input type="text" class="form-control" v-model="form.technician_name">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Assistant Technician</label>
            <input type="text" class="form-control" v-model="form.assistant_technician">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Service Location</label>
            <input type="text" class="form-control" v-model="form.service_location">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Service Type</label>
            <select class="form-control" v-model="form.service_type">
              <option value="">Not selected</option>
              <option value="full_onsite_assembly">Full QuiviCraft On-Site Assembly</option>
              <option value="full_onsite_assembly_tag_along">Full QuiviCraft On-Site Assembly with Tag Along</option>
              <option value="studio_assembly">Studio Assembly</option>
            </select>
          </div>
        </div>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Report Information
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'report_version', 'service_date', 'handover_completion_time',
  'technician_name', 'assistant_technician', 'service_location', 'service_type', 'status',
];

const BOOLEAN_KEYS = [];

const STRING_KEYS = [
  'report_version', 'service_date', 'handover_completion_time',
  'technician_name', 'assistant_technician', 'service_location', 'service_type', 'status',
];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      saving: false,
      errors: [],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
    },
  },
  methods: {
    buildForm(data) {
      const form = {};
      FIELD_KEYS.forEach(key => {
        if (BOOLEAN_KEYS.includes(key)) {
          form[key] = Boolean(data[key]);
        } else if (STRING_KEYS.includes(key)) {
          form[key] = data[key] || '';
        } else {
          form[key] = data[key] !== undefined && data[key] !== null ? data[key] : null;
        }
      });
      if (!form.status) {
        form.status = 'in_progress';
      }
      return form;
    },
    async save() {
      this.saving = true;
      this.errors = [];

      const payload = {};
      FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        if (BOOLEAN_KEYS.includes(key)) {
          payload[key] = value ? '1' : '0';
        } else {
          payload[key] = value === null || value === undefined ? '' : value;
        }
      });

      try {
        const res = await axios.post(`${this.apiBase}/report-info`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Report information updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save report information'];
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>
```

- [ ] **Step 2: Write `BuildInfoSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Build Information</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Looked up from this order (read-only)</h6>
      <div class="row">
        <div class="col-md-3"><small class="text-muted d-block">QuiviCraft ID</small>{{ quivicraftId || '—' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">QuiviCraft Plan</small>{{ quivicraftPlan || '—' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">QuiviServe ID</small>{{ quiviserveId || '—' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">QuiviServe Customer ID</small>{{ quiviserveCustomerId || '—' }}</div>
        <div class="col-md-3 mt-2"><small class="text-muted d-block">QuiviServe Plan</small>{{ quiviservePlan || '—' }}</div>
        <div class="col-md-3 mt-2"><small class="text-muted d-block">QuiviCare ID</small>{{ quivicareId || '—' }}</div>
        <div class="col-md-3 mt-2"><small class="text-muted d-block">QuiviCare Plan</small>{{ quivicarePlan || '—' }}</div>
      </div>

      <h6 class="text-muted mt-3">Technician-entered</h6>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label class="form-label">Operating System</label>
            <input type="text" class="form-control" v-model="form.operating_system">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="form-label">Operating System Version</label>
            <input type="text" class="form-control" v-model="form.operating_system_version">
          </div>
        </div>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Build Information
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = ['operating_system', 'operating_system_version'];
const BOOLEAN_KEYS = [];
const STRING_KEYS = ['operating_system', 'operating_system_version'];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
    quivicraftId: { type: String, default: null },
    quivicraftPlan: { type: String, default: null },
    quiviserveId: { type: String, default: null },
    quiviserveCustomerId: { type: String, default: null },
    quiviservePlan: { type: String, default: null },
    quivicareId: { type: String, default: null },
    quivicarePlan: { type: String, default: null },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      saving: false,
      errors: [],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
    },
  },
  methods: {
    buildForm(data) {
      const form = {};
      FIELD_KEYS.forEach(key => {
        if (BOOLEAN_KEYS.includes(key)) {
          form[key] = Boolean(data[key]);
        } else if (STRING_KEYS.includes(key)) {
          form[key] = data[key] || '';
        } else {
          form[key] = data[key] !== undefined && data[key] !== null ? data[key] : null;
        }
      });
      return form;
    },
    async save() {
      this.saving = true;
      this.errors = [];

      const payload = {};
      FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        if (BOOLEAN_KEYS.includes(key)) {
          payload[key] = value ? '1' : '0';
        } else {
          payload[key] = value === null || value === undefined ? '' : value;
        }
      });

      try {
        const res = await axios.post(`${this.apiBase}/build-info`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Build information updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save build information'];
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>
```

- [ ] **Step 3: Write `StudioDocsSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Studio Documentation Verification</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Looked up from this order (read-only)</h6>
      <div class="row">
        <div class="col-md-6">
          <small class="text-muted d-block">Studio Inspection Report</small>
          <span :class="studioInspectionReportCompleted ? 'text-success' : 'text-muted'">
            {{ studioInspectionReportCompleted ? 'Completed' : 'Not completed' }}
          </span>
          <span v-if="studioInspectionReportId"> — ID {{ studioInspectionReportId }}</span>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Performance Testing Report</small>
          <span :class="performanceTestingReportCompleted ? 'text-success' : 'text-muted'">
            {{ performanceTestingReportCompleted ? 'Completed' : 'Not completed' }}
          </span>
          <span v-if="performanceTestingReportId"> — ID {{ performanceTestingReportId }}</span>
        </div>
      </div>

      <h6 class="text-muted mt-3">Verification</h6>
      <div class="custom-control custom-checkbox mb-2">
        <input type="checkbox" class="custom-control-input" id="sd-seal" v-model="form.security_seal_verified_before_delivery">
        <label class="custom-control-label" for="sd-seal">QuiviTech Security Seal Verified Before Delivery</label>
      </div>

      <div class="form-group mt-2">
        <label class="form-label">Notes</label>
        <textarea class="form-control" rows="2" v-model="form.studio_docs_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Studio Documentation Verification
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = ['security_seal_verified_before_delivery', 'studio_docs_notes'];
const BOOLEAN_KEYS = ['security_seal_verified_before_delivery'];
const STRING_KEYS = ['studio_docs_notes'];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
    studioInspectionReportCompleted: { type: Boolean, default: false },
    studioInspectionReportId: { type: [Number, String], default: null },
    performanceTestingReportCompleted: { type: Boolean, default: false },
    performanceTestingReportId: { type: [Number, String], default: null },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      saving: false,
      errors: [],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
    },
  },
  methods: {
    buildForm(data) {
      const form = {};
      FIELD_KEYS.forEach(key => {
        if (BOOLEAN_KEYS.includes(key)) {
          form[key] = Boolean(data[key]);
        } else if (STRING_KEYS.includes(key)) {
          form[key] = data[key] || '';
        } else {
          form[key] = data[key] !== undefined && data[key] !== null ? data[key] : null;
        }
      });
      return form;
    },
    async save() {
      this.saving = true;
      this.errors = [];

      const payload = {};
      FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        if (BOOLEAN_KEYS.includes(key)) {
          payload[key] = value ? '1' : '0';
        } else {
          payload[key] = value === null || value === undefined ? '' : value;
        }
      });

      try {
        const res = await axios.post(`${this.apiBase}/studio-docs`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Studio documentation verification updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save studio documentation verification'];
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>
```

- [ ] **Step 4: Write `CustomerAcceptanceSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Customer Acceptance</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'ca-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'ca-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <div class="form-group mt-2">
        <label class="form-label">Notes</label>
        <textarea class="form-control" rows="2" v-model="form.customer_acceptance_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Customer Acceptance
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = [
  'physical_condition_accepted', 'system_boot_verified', 'display_verified', 'accessories_received',
  'documentation_received', 'customer_demonstration_completed', 'customer_questions_addressed',
  'customer_acceptance_notes',
];

const BOOLEAN_KEYS = [
  'physical_condition_accepted', 'system_boot_verified', 'display_verified', 'accessories_received',
  'documentation_received', 'customer_demonstration_completed', 'customer_questions_addressed',
];

const STRING_KEYS = ['customer_acceptance_notes'];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      saving: false,
      errors: [],
      checklistFields: [
        { key: 'physical_condition_accepted', label: 'Physical Condition Accepted' },
        { key: 'system_boot_verified', label: 'System Boot Verified' },
        { key: 'display_verified', label: 'Display Verified' },
        { key: 'accessories_received', label: 'Accessories Received' },
        { key: 'documentation_received', label: 'Documentation Received' },
        { key: 'customer_demonstration_completed', label: 'Customer Demonstration Completed' },
        { key: 'customer_questions_addressed', label: 'Customer Questions Addressed' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
    },
  },
  methods: {
    buildForm(data) {
      const form = {};
      FIELD_KEYS.forEach(key => {
        if (BOOLEAN_KEYS.includes(key)) {
          form[key] = Boolean(data[key]);
        } else if (STRING_KEYS.includes(key)) {
          form[key] = data[key] || '';
        } else {
          form[key] = data[key] !== undefined && data[key] !== null ? data[key] : null;
        }
      });
      return form;
    },
    async save() {
      this.saving = true;
      this.errors = [];

      const payload = {};
      FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        if (BOOLEAN_KEYS.includes(key)) {
          payload[key] = value ? '1' : '0';
        } else {
          payload[key] = value === null || value === undefined ? '' : value;
        }
      });

      try {
        const res = await axios.post(`${this.apiBase}/customer-acceptance`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Customer acceptance updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save customer acceptance'];
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/onsite_handover_studio/ReportInfoSection.vue resources/js/components/onsite_handover_studio/BuildInfoSection.vue resources/js/components/onsite_handover_studio/StudioDocsSection.vue resources/js/components/onsite_handover_studio/CustomerAcceptanceSection.vue
git commit -m "Add Report Info, Build Info, Studio Docs, and Customer Acceptance section components"
```

---

### Task 6: Frontend — Arrival, Post-Transport, Post-Handover sections (with photos)

**Files:**
- Create: `resources/js/components/onsite_handover_studio/ArrivalSection.vue`
- Create: `resources/js/components/onsite_handover_studio/PostTransportSection.vue`
- Create: `resources/js/components/onsite_handover_studio/PostHandoverSection.vue`

**Interfaces:**
- Consumes: the existing shared `resources/js/components/shared/PhotoUploadField.vue` (already built for OnSite Handover (QuiviCraft) — do not create a new one); `POST {apiBase}/arrival`, `.../post-transport`, `.../post-handover` (Task 3).
- Produces: each emits `saved` with the response `data`. All 3 follow the exact single-photo-field pattern OnSite Handover (QuiviCraft)'s `ArrivalSection.vue` established — read that file for the FormData/multipart submission pattern to copy exactly.

- [ ] **Step 1: Write `ArrivalSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">On-Site Arrival Verification</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'arr-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'arr-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <photo-upload-field
        :existing-photos="existingPhotos"
        :new-photos="newPhotos"
        @add-photos="addPhotos"
        @remove-existing="removeExistingPhoto"
        @remove-new="removeNewPhoto"
      />

      <div class="form-group mt-2">
        <label class="form-label">Notes</label>
        <textarea class="form-control" rows="2" v-model="form.arrival_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Arrival Verification
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import PhotoUploadField from '../shared/PhotoUploadField.vue';

const FIELD_KEYS = [
  'arrival_time', 'workspace_available', 'power_outlet_available', 'display_available',
  'keyboard_available', 'mouse_available', 'internet_available', 'arrival_notes',
];

const BOOLEAN_KEYS = [
  'workspace_available', 'power_outlet_available', 'display_available',
  'keyboard_available', 'mouse_available', 'internet_available',
];

const STRING_KEYS = ['arrival_time', 'arrival_notes'];

export default {
  components: { PhotoUploadField },
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      existingPhotos: this.initialData.arrival_photos || [],
      newPhotos: [],
      removePhotos: [],
      saving: false,
      errors: [],
      checklistFields: [
        { key: 'workspace_available', label: 'Workspace Available' },
        { key: 'power_outlet_available', label: 'Power Outlet Available' },
        { key: 'display_available', label: 'Display Available' },
        { key: 'keyboard_available', label: 'Keyboard Available' },
        { key: 'mouse_available', label: 'Mouse Available' },
        { key: 'internet_available', label: 'Internet Available (Optional)' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.existingPhotos = newVal.arrival_photos || [];
      this.newPhotos = [];
      this.removePhotos = [];
    },
  },
  methods: {
    buildForm(data) {
      const form = {};
      FIELD_KEYS.forEach(key => {
        if (BOOLEAN_KEYS.includes(key)) {
          form[key] = Boolean(data[key]);
        } else if (STRING_KEYS.includes(key)) {
          form[key] = data[key] || '';
        } else {
          form[key] = data[key] !== undefined && data[key] !== null ? data[key] : null;
        }
      });
      return form;
    },
    addPhotos(files) {
      Array.from(files).forEach(f => this.newPhotos.push(f));
    },
    removeExistingPhoto(path) {
      this.existingPhotos = this.existingPhotos.filter(p => p !== path);
      this.removePhotos.push(path);
    },
    removeNewPhoto(idx) {
      this.newPhotos.splice(idx, 1);
    },
    async save() {
      this.saving = true;
      this.errors = [];

      const formData = new FormData();
      FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        if (BOOLEAN_KEYS.includes(key)) {
          formData.append(key, value ? '1' : '0');
        } else {
          formData.append(key, value === null || value === undefined ? '' : value);
        }
      });
      this.newPhotos.forEach(f => formData.append('arrival_photos[]', f));
      this.removePhotos.forEach(p => formData.append('remove_arrival_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/arrival`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.newPhotos = [];
        this.removePhotos = [];
        this.existingPhotos = res.data.data.arrival_photos || [];
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Arrival verification updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save arrival verification'];
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>
```

- [ ] **Step 2: Write `PostTransportSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Post-Transport Hardware Verification</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'pt-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'pt-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <photo-upload-field
        :existing-photos="existingPhotos"
        :new-photos="newPhotos"
        @add-photos="addPhotos"
        @remove-existing="removeExistingPhoto"
        @remove-new="removeNewPhoto"
      />

      <div class="form-group mt-2">
        <label class="form-label">Notes</label>
        <textarea class="form-control" rows="2" v-model="form.post_transport_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Post-Transport Hardware Verification
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import PhotoUploadField from '../shared/PhotoUploadField.vue';

const FIELD_KEYS = [
  'gpu_securely_installed', 'memory_fully_seated', 'cpu_cooler_secure', 'power_connections_secure',
  'storage_secure', 'no_loose_cables', 'no_loose_screws', 'post_transport_notes',
];

const BOOLEAN_KEYS = [
  'gpu_securely_installed', 'memory_fully_seated', 'cpu_cooler_secure', 'power_connections_secure',
  'storage_secure', 'no_loose_cables', 'no_loose_screws',
];

const STRING_KEYS = ['post_transport_notes'];

export default {
  components: { PhotoUploadField },
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      existingPhotos: this.initialData.post_transport_photos || [],
      newPhotos: [],
      removePhotos: [],
      saving: false,
      errors: [],
      checklistFields: [
        { key: 'gpu_securely_installed', label: 'GPU Securely Installed' },
        { key: 'memory_fully_seated', label: 'Memory Fully Seated' },
        { key: 'cpu_cooler_secure', label: 'CPU Cooler Secure' },
        { key: 'power_connections_secure', label: 'Power Connections Secure' },
        { key: 'storage_secure', label: 'Storage Secure' },
        { key: 'no_loose_cables', label: 'No Loose Cables' },
        { key: 'no_loose_screws', label: 'No Loose Screws' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.existingPhotos = newVal.post_transport_photos || [];
      this.newPhotos = [];
      this.removePhotos = [];
    },
  },
  methods: {
    buildForm(data) {
      const form = {};
      FIELD_KEYS.forEach(key => {
        if (BOOLEAN_KEYS.includes(key)) {
          form[key] = Boolean(data[key]);
        } else if (STRING_KEYS.includes(key)) {
          form[key] = data[key] || '';
        } else {
          form[key] = data[key] !== undefined && data[key] !== null ? data[key] : null;
        }
      });
      return form;
    },
    addPhotos(files) {
      Array.from(files).forEach(f => this.newPhotos.push(f));
    },
    removeExistingPhoto(path) {
      this.existingPhotos = this.existingPhotos.filter(p => p !== path);
      this.removePhotos.push(path);
    },
    removeNewPhoto(idx) {
      this.newPhotos.splice(idx, 1);
    },
    async save() {
      this.saving = true;
      this.errors = [];

      const formData = new FormData();
      FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        if (BOOLEAN_KEYS.includes(key)) {
          formData.append(key, value ? '1' : '0');
        } else {
          formData.append(key, value === null || value === undefined ? '' : value);
        }
      });
      this.newPhotos.forEach(f => formData.append('post_transport_photos[]', f));
      this.removePhotos.forEach(p => formData.append('remove_post_transport_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/post-transport`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.newPhotos = [];
        this.removePhotos = [];
        this.existingPhotos = res.data.data.post_transport_photos || [];
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Post-transport hardware verification updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save post-transport hardware verification'];
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>
```

- [ ] **Step 3: Write `PostHandoverSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Post-Handover System Verification</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'ph-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'ph-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <photo-upload-field
        :existing-photos="existingPhotos"
        :new-photos="newPhotos"
        @add-photos="addPhotos"
        @remove-existing="removeExistingPhoto"
        @remove-new="removeNewPhoto"
      />

      <div class="form-group mt-2">
        <label class="form-label">Notes</label>
        <textarea class="form-control" rows="2" v-model="form.post_handover_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Post-Handover System Verification
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import PhotoUploadField from '../shared/PhotoUploadField.vue';

const FIELD_KEYS = [
  'system_powered_on', 'post_successful', 'windows_boot_successful', 'display_output_verified',
  'network_connected', 'internet_accessible', 'audio_verified', 'usb_ports_verified',
  'rgb_lighting_verified', 'post_handover_notes',
];

const BOOLEAN_KEYS = [
  'system_powered_on', 'post_successful', 'windows_boot_successful', 'display_output_verified',
  'network_connected', 'internet_accessible', 'audio_verified', 'usb_ports_verified',
  'rgb_lighting_verified',
];

const STRING_KEYS = ['post_handover_notes'];

export default {
  components: { PhotoUploadField },
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      existingPhotos: this.initialData.post_handover_photos || [],
      newPhotos: [],
      removePhotos: [],
      saving: false,
      errors: [],
      checklistFields: [
        { key: 'system_powered_on', label: 'System Powered On' },
        { key: 'post_successful', label: 'POST Successful' },
        { key: 'windows_boot_successful', label: 'Windows Boot Successful' },
        { key: 'display_output_verified', label: 'Display Output Verified' },
        { key: 'network_connected', label: 'Network Connected' },
        { key: 'internet_accessible', label: 'Internet Accessible' },
        { key: 'audio_verified', label: 'Audio Verified' },
        { key: 'usb_ports_verified', label: 'USB Ports Verified' },
        { key: 'rgb_lighting_verified', label: 'RGB Lighting Verified' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.existingPhotos = newVal.post_handover_photos || [];
      this.newPhotos = [];
      this.removePhotos = [];
    },
  },
  methods: {
    buildForm(data) {
      const form = {};
      FIELD_KEYS.forEach(key => {
        if (BOOLEAN_KEYS.includes(key)) {
          form[key] = Boolean(data[key]);
        } else if (STRING_KEYS.includes(key)) {
          form[key] = data[key] || '';
        } else {
          form[key] = data[key] !== undefined && data[key] !== null ? data[key] : null;
        }
      });
      return form;
    },
    addPhotos(files) {
      Array.from(files).forEach(f => this.newPhotos.push(f));
    },
    removeExistingPhoto(path) {
      this.existingPhotos = this.existingPhotos.filter(p => p !== path);
      this.removePhotos.push(path);
    },
    removeNewPhoto(idx) {
      this.newPhotos.splice(idx, 1);
    },
    async save() {
      this.saving = true;
      this.errors = [];

      const formData = new FormData();
      FIELD_KEYS.forEach(key => {
        const value = this.form[key];
        if (BOOLEAN_KEYS.includes(key)) {
          formData.append(key, value ? '1' : '0');
        } else {
          formData.append(key, value === null || value === undefined ? '' : value);
        }
      });
      this.newPhotos.forEach(f => formData.append('post_handover_photos[]', f));
      this.removePhotos.forEach(p => formData.append('remove_post_handover_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/post-handover`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.newPhotos = [];
        this.removePhotos = [];
        this.existingPhotos = res.data.data.post_handover_photos || [];
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Post-handover system verification updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save post-handover system verification'];
        }
        Swal.fire('Error!', this.errors.join('<br>'), 'error');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.form-label { font-weight: 600; color: #495057; font-size: 0.85rem; }
</style>
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/onsite_handover_studio/ArrivalSection.vue resources/js/components/onsite_handover_studio/PostTransportSection.vue resources/js/components/onsite_handover_studio/PostHandoverSection.vue
git commit -m "Add Arrival, Post-Transport, and Post-Handover section components"
```

---

### Task 7: Frontend — `index.vue` wiring

**Files:**
- Create: `resources/js/components/onsite_handover_studio/index.vue`

**Interfaces:**
- Consumes: `GET {apiBase}` (Task 2's `show()`) and all 7 section components (Tasks 5-6).
- Produces: the routable page `onsitehandoverstudio` (registered in Task 4) becomes functional.

- [ ] **Step 1: Write `index.vue`**

```vue
<template>
  <div class="container-fluid">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border"></div>
    </div>
    <template v-else>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">OnSite Handover (Studio) — {{ order.order_id }}</h4>
          <small class="text-muted">Report {{ onsiteHandoverStudio.report_id }} · Round {{ onsiteHandoverStudio.round }}</small>
        </div>
      </div>

      <report-info-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        @saved="onReportInfoSaved"
      />
      <build-info-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        :quivicraft-id="derived.quivicraft_id"
        :quivicraft-plan="derived.quivicraft_plan"
        :quiviserve-id="derived.quiviserve_id"
        :quiviserve-customer-id="derived.quiviserve_customer_id"
        :quiviserve-plan="derived.quiviserve_plan"
        :quivicare-id="derived.quivicare_id"
        :quivicare-plan="derived.quivicare_plan"
        @saved="onBuildInfoSaved"
      />
      <studio-docs-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        :studio-inspection-report-completed="derived.studio_inspection_report_completed"
        :studio-inspection-report-id="derived.studio_inspection_report_id"
        :performance-testing-report-completed="derived.performance_testing_report_completed"
        :performance-testing-report-id="derived.performance_testing_report_id"
        @saved="onStudioDocsSaved"
      />
      <arrival-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        @saved="onArrivalSaved"
      />
      <post-transport-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        @saved="onPostTransportSaved"
      />
      <post-handover-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        @saved="onPostHandoverSaved"
      />
      <customer-acceptance-section
        :api-base="apiBase"
        :initial-data="onsiteHandoverStudio"
        @saved="onCustomerAcceptanceSaved"
      />
    </template>
  </div>
</template>

<script>
import axios from 'axios';
import ReportInfoSection from './ReportInfoSection.vue';
import BuildInfoSection from './BuildInfoSection.vue';
import StudioDocsSection from './StudioDocsSection.vue';
import ArrivalSection from './ArrivalSection.vue';
import PostTransportSection from './PostTransportSection.vue';
import PostHandoverSection from './PostHandoverSection.vue';
import CustomerAcceptanceSection from './CustomerAcceptanceSection.vue';

export default {
  components: {
    ReportInfoSection,
    BuildInfoSection,
    StudioDocsSection,
    ArrivalSection,
    PostTransportSection,
    PostHandoverSection,
    CustomerAcceptanceSection,
  },
  data() {
    return {
      order: {},
      onsiteHandoverStudio: null,
      derived: {},
      loading: true,
    };
  },
  computed: {
    orderId() {
      return this.$route.params.id;
    },
    round() {
      return this.$route.params.round || 1;
    },
    apiBase() {
      return `/api/order/${this.orderId}/onsite-handover-studio/${this.round}`;
    },
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      this.loading = true;
      axios.get(this.apiBase)
        .then(res => {
          const data = res.data.data;
          this.order = data.order;
          this.onsiteHandoverStudio = data.onsite_handover_studio;
          this.derived = {
            studio_inspection_report_completed: data.studio_inspection_report_completed,
            studio_inspection_report_id: data.studio_inspection_report_id,
            performance_testing_report_completed: data.performance_testing_report_completed,
            performance_testing_report_id: data.performance_testing_report_id,
            quivicraft_id: data.quivicraft_id,
            quivicraft_plan: data.quivicraft_plan,
            quiviserve_id: data.quiviserve_id,
            quiviserve_customer_id: data.quiviserve_customer_id,
            quiviserve_plan: data.quiviserve_plan,
            quivicare_id: data.quivicare_id,
            quivicare_plan: data.quivicare_plan,
          };
        })
        .finally(() => {
          this.loading = false;
        });
    },
    onReportInfoSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    onBuildInfoSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    onStudioDocsSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    onArrivalSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    onPostTransportSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    onPostHandoverSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
    onCustomerAcceptanceSaved(data) {
      Object.assign(this.onsiteHandoverStudio, data);
    },
  },
};
</script>
```

**Critical: every `@saved` handler uses `Object.assign(this.onsiteHandoverStudio, data)`, NOT `this.onsiteHandoverStudio = data`.** All 7 sections share the same `initial-data` object reference (single table, no child data), so a full reassignment would create a new object reference and spuriously fire every OTHER section's `watch: { initialData }`, wiping their unsaved in-progress edits — this is the exact bug OnSite Handover (QuiviCraft)'s final review found and fixed. Do not regress it here.

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/onsite_handover_studio/index.vue
git commit -m "Wire OnSite Handover (Studio) index.vue"
```

---

### Task 8: Quick-launch button + vault documentation

**Files:**
- Modify: `resources/js/components/order/allorder.vue`
- Modify: `docs/QuiviTech/QuiviCraft.md`
- Modify: `docs/QuiviTech/Domain-Models.md`
- Modify: `docs/QuiviTech/API-Routes.md`
- Modify: `docs/QuiviTech/Frontend-Components.md`

**Interfaces:**
- Consumes: the `onsitehandoverstudio` route name (Task 4).
- None produced — final task.

- [ ] **Step 1: Add the quick-launch button**

In `resources/js/components/order/allorder.vue`, find the OnSite Handover (QuiviCraft) button added in that feature's own plan:

```html
                                                        <router-link
                                                            :to="{name:'onsitehandover', params:{id:order.id, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="OnSite Handover"
                                                        >
                                                            <i class="fas fa-truck-loading"></i>
                                                        </router-link>
```

Replace with:

```html
                                                        <router-link
                                                            :to="{name:'onsitehandover', params:{id:order.id, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="OnSite Handover"
                                                        >
                                                            <i class="fas fa-truck-loading"></i>
                                                        </router-link>
                                                        <router-link
                                                            :to="{name:'onsitehandoverstudio', params:{id:order.id, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="OnSite Handover (Studio)"
                                                        >
                                                            <i class="fas fa-dolly"></i>
                                                        </router-link>
```

- [ ] **Step 2: Update `docs/QuiviTech/QuiviCraft.md`**

Find the `## 6. OnSite Handover (QuiviCraft)` section and add a new section immediately after it:

```markdown

## 7. OnSite Handover (Studio)

The last of the 4 QuiviCare QC report sub-projects — shipped 2026-07-28. A lighter sibling of [[QuiviCraft]]'s own OnSite Handover: covers delivery of a PC built entirely in-studio (no on-site assembly). 7 sections instead of 11 — no Customer Information section, no packaging-condition Transportation Inspection (replaced by a lighter "Post-Transport Hardware Verification" checking the pre-built unit's internals are still secure), no Acknowledgement/signatures, no assembly checklist.

- **`OnsiteHandoverStudio`** — same single-table architecture as the QuiviCraft version (table `onsite_handovers_studio`), one per `(order_id, round)`, own `report_id` business code (`OSH-STD-XXXX`), same 4-value `status` set even though the source doc doesn't show a Report Status field (kept for consistency with every other QC report in this app).
- 7 sections, each with its own save endpoint: Report Information, Build Information, Studio Documentation Verification, On-Site Arrival Verification, Post-Transport Hardware Verification, Post-Handover System Verification, Customer Acceptance.
- Reuses the shared `PhotoUploadField.vue` component (no new photo widget needed) and the same auto-populated read-only cross-report/tier lookup pattern as the QuiviCraft version.
- Route/API shape is `order/{orderId}/onsite-handover-studio/{round}` — quick-launch button on the QuiviCraft list sits next to the other 3 QC report buttons. This completes all 4 sub-projects of the QuiviCare QC Report system.
```

- [ ] **Step 3: Update `docs/QuiviTech/Domain-Models.md`**

Find where OnSite Handover (QuiviCraft)'s model is documented and add a new paragraph after it:

```markdown
- **OnSite Handover (Studio), added 2026-07-28** — `OnsiteHandoverStudio` (table `onsite_handovers_studio`): same shape as `OnsiteHandover` (`SoftDeletes`, `$guarded = ['id']`, full `$casts`, single table, no child tables), one row per `(order_id, round)`. 3 `*_photos` JSON array columns (arrival, post-transport, post-handover — fewer sections have Pictures than the QuiviCraft version). Reuses the exact "one column, one writer" discipline for its own `arrival_time` duplicate-field quirk.
```

- [ ] **Step 4: Update `docs/QuiviTech/API-Routes.md`**

Find the OnSite Handover (QuiviCraft) section and add a new section immediately after it:

```markdown

## OnSite Handover (Studio, added 2026-07-28)
- `prefix: order/{orderId}/onsite-handover-studio/{round}` → `OnsiteHandoverStudioController`: `show` (GET `/`, creates the row + generates `report_id` with the `OSH-STD-` prefix on first access, returns the same shape of derived/read-only fields as the QuiviCraft version), then 7 section actions each posting only their own column subset: `updateReportInfo` (`.../report-info`), `updateBuildInfo` (`.../build-info`), `updateStudioDocs` (`.../studio-docs`), `updateArrival` (`.../arrival`), `updatePostTransport` (`.../post-transport`), `updatePostHandover` (`.../post-handover`), `updateCustomerAcceptance` (`.../customer-acceptance`). No `complete` action, same as the QuiviCraft version.
```

- [ ] **Step 5: Update `docs/QuiviTech/Frontend-Components.md`**

Find where `PhotoUploadField.vue`'s usage list is documented and append the 3 new Studio sections that also use it:

```markdown
Also used by `onsite_handover_studio/ArrivalSection.vue`, `PostTransportSection.vue`, `PostHandoverSection.vue` (added 2026-07-28) — same component, no changes needed for reuse.
```

Then find where OnSite Handover (QuiviCraft)'s section components are documented and add a paragraph describing the new `onsite_handover_studio/` directory: 7 self-contained section components, following the identical `apiBase`/`initialData` + `FIELD_KEYS` + `save()`-emits-`saved` contract, mounted from `onsite_handover_studio/index.vue` — the last of the 4 QuiviCare QC report feature directories.

- [ ] **Step 6: Lint the modified Vue file**

Run a manual read-through of `allorder.vue`'s changed section confirming the new `router-link` block is well-formed and doesn't break the surrounding button group's HTML structure.

- [ ] **Step 7: Commit**

```bash
git add resources/js/components/order/allorder.vue docs/QuiviTech/QuiviCraft.md docs/QuiviTech/Domain-Models.md docs/QuiviTech/API-Routes.md docs/QuiviTech/Frontend-Components.md
git commit -m "Add OnSite Handover (Studio) quick-launch button and vault documentation"
```

---

## Post-plan verification (controller, not subagents)

After all 8 tasks are complete and reviewed, the controller must:

1. Run `php artisan migrate` against the dev DB and confirm the `onsite_handovers_studio` table via `DESCRIBE`.
2. curl `show()` for a test order, confirming `report_id` auto-generated in `OSH-STD-XXXX` format and all derived lookup fields resolve correctly.
3. curl each of the 7 section endpoints, confirming each only writes its own columns — specifically: POST to `report-info` must NOT change `arrival_time`; POST to `arrival` must NOT change any `report-info`-owned column (the single most important regression check, same as the QuiviCraft version's).
4. curl a photo upload + removal round-trip on at least one photo-bearing section, confirming files land under `storage/app/public/onsite-handovers-studio/`.
5. Confirm the quick-launch button routes correctly and the frontend bundle compiles cleanly with no Vue/webpack errors.
6. Clean up all test-order rows created during verification via `forceDelete()` in tinker, scoped to the specific test order's `onsite_handovers_studio` row (single table, no children to clean up).
