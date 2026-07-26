# OnSite Handover (QuiviCraft) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship the OnSite Handover (QuiviCraft) QC report — an 11-section report a technician fills out during an on-site PC assembly visit, covering arrival, transport-damage check, assembly, post-build verification, customer acceptance, and acknowledgement.

**Architecture:** One new table `onsite_handovers` (single wide table, no child tables — unlike Craft Inspection/Performance Testing, this report has no repeated/swappable sub-forms). 11 section-scoped save endpoints each write a disjoint column subset of the same row (progressive-save, protects a multi-hour on-site visit's data). Several Build Information / Studio Documentation fields are looked up live from the order's existing CraftInspection/PerformanceTest/ServeData/CareData records and returned read-only — never stored as columns here.

**Tech Stack:** Laravel 7 (PHP), Eloquent, Vue 2 Options API, Bootstrap 4, axios, SweetAlert2.

## Global Constraints

- No automated test suite exists in this codebase (confirmed across every prior QC report feature). Verification is manual: migrate + `DESCRIBE`, curl each endpoint, browser-adjacent static checks. Implementer/reviewer subagents have no Docker/PHP/Node access in this environment — their self-checks are manual brace-balance/read-through, not `php -l`/`npm run watch`. The controller (dispatching agent) runs the real migration and curls every endpoint live after each task, same as every prior QC report plan.
- **One column, one writer.** Every `onsite_handovers` column must be writable by exactly one of the 11 section endpoints. `arrival_time` is the one field the source doc mentions in two places (a Report Information summary line and the fuller On-Site Arrival Verification section) — it is written ONLY by `updateArrival()`; `updateReportInfo()`'s field list must NOT include it, even though `show()` displays the row's current value everywhere it's asked for. This is the same "second write path" bug class fixed three times during Performance Testing (Phases 2, 3, and proactively in Phase 4) — get it right from the start here, there is no separate fix-up task budgeted for it.
- Every new Vue section component follows the exact shape Performance Testing's sections established: `apiBase`/`initialData` props, `FIELD_KEYS`/`BOOLEAN_KEYS`/`STRING_KEYS` constants, `buildForm()` used in both `data()` and a `watch: { initialData(newVal) { this.form = this.buildForm(newVal); } }`, a `save()` method that posts only its own `FIELD_KEYS`, emits `saved` with the response's `data`, and never calls a full-page re-fetch. Sections with photos additionally track `newXPhotos`/`removeXPhotos` local arrays and submit via `FormData`/`multipart/form-data` (see `performance_test/index.vue`'s `saveForm()`/`saveItem()` for the exact multipart pattern to copy).
- Every controller update method follows the exact shape of `PerformanceTestController::updateStorageResults()` (validate → 404 if row missing → `fill($request->only(self::X_FIELDS))` → merge photos if the section has any → `save()` → return `['success' => true, 'message' => ..., 'data' => $handover->fresh()]`), adapted to this feature's single-table design: there is no per-section child model to `firstOrCreate()` — `$handover` is the same row for every section, already created by `show()`.
- No `complete()` action exists for this feature — `status` is a normal field on `updateReportInfo()`, set directly by the technician picking a dropdown value (`in_progress`/`completed`/`deferred`/`cancelled`). Do not add a separate complete endpoint.
- Photo fields (`arrival_photos`, `transport_case_photos`, `component_packaging_photos`, `assembly_photos`, `post_build_hardware_photos`, `post_build_software_photos`) are optional, multi-file, no required-count validation — reuse `PerformanceTestController`'s private `mergePhotos()`/`storePhotos()` helpers, ported into `OnsiteHandoverController` (copy them verbatim, this codebase doesn't currently share them via a trait and this plan doesn't introduce one — YAGNI until a third controller needs them).
- MySQL 64-char constraint-name limit: table name `onsite_handovers` (17 chars) keeps every default constraint name (`onsite_handovers_order_id_round_unique` = 39 chars, `onsite_handovers_order_id_foreign` = 34 chars) safely under 64. No custom short constraint names needed anywhere in this plan.
- Spec source of truth: `docs/superpowers/specs/2026-07-26-onsite-handover-quivicraft-design.md`. Column names, types, and section boundaries in this plan are transcribed directly from it — do not invent alternate names.

---

### Task 1: Migration + Model

**Files:**
- Create: `database/migrations/2026_07_27_200000_create_onsite_handovers_table.php`
- Create: `app/Models/OnsiteHandover.php`

**Interfaces:**
- Produces: the `onsite_handovers` table and `OnsiteHandover` model — every later task depends on these exact column names.

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnsiteHandoversTable extends Migration
{
    public function up()
    {
        Schema::create('onsite_handovers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('round')->default(1);
            $table->string('report_id')->unique();
            $table->string('report_version')->nullable();
            $table->string('status')->default('in_progress');

            // Report Information
            $table->date('service_date')->nullable();
            $table->time('arrival_time')->nullable();
            $table->time('work_start_time')->nullable();
            $table->time('work_completion_time')->nullable();
            $table->string('technician_name')->nullable();
            $table->string('assistant_technician')->nullable();
            $table->string('service_location')->nullable();
            $table->string('service_type')->nullable();

            // Customer Information
            $table->string('customer_present_during_assembly')->nullable();
            $table->string('authorised_representative')->nullable();
            $table->string('service_address')->nullable();

            // Build Information
            $table->string('pc_purpose')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('operating_system_version')->nullable();

            // Studio Documentation Verification
            $table->boolean('component_serial_numbers_matched')->nullable();
            $table->boolean('customer_order_specification_verified')->nullable();
            $table->boolean('required_components_present')->nullable();
            $table->boolean('required_tools_present')->nullable();
            $table->boolean('required_consumables_present')->nullable();
            $table->text('studio_docs_notes')->nullable();

            // On-Site Arrival Verification
            $table->string('service_environment')->nullable();
            $table->boolean('workspace_available')->nullable();
            $table->boolean('adequate_lighting')->nullable();
            $table->boolean('stable_work_surface')->nullable();
            $table->boolean('sufficient_working_space')->nullable();
            $table->boolean('power_outlet_available')->nullable();
            $table->boolean('internet_available')->nullable();
            $table->boolean('customer_present_at_arrival')->nullable();
            $table->boolean('assembly_area_approved_by_customer')->nullable();
            $table->json('arrival_photos')->nullable();
            $table->text('arrival_notes')->nullable();

            // Transportation Inspection
            $table->string('transport_case_note')->nullable();
            $table->string('transport_case_status')->nullable();
            $table->json('transport_case_photos')->nullable();
            $table->string('component_packaging_note')->nullable();
            $table->string('component_packaging_status')->nullable();
            $table->json('component_packaging_photos')->nullable();
            $table->boolean('security_seal_intact')->nullable();
            $table->boolean('no_signs_of_transit_damage')->nullable();
            $table->boolean('accessories_present')->nullable();
            $table->boolean('documentation_present')->nullable();
            $table->text('transportation_notes')->nullable();
            $table->string('transportation_verdict')->nullable();

            // QuiviCraft Assembly
            $table->boolean('cpu_installed')->nullable();
            $table->boolean('memory_installed')->nullable();
            $table->boolean('storage_installed')->nullable();
            $table->boolean('cpu_cooler_installed')->nullable();
            $table->boolean('motherboard_installed')->nullable();
            $table->boolean('power_supply_installed')->nullable();
            $table->boolean('case_fans_installed')->nullable();
            $table->boolean('graphics_card_installed')->nullable();
            $table->boolean('cable_management_completed')->nullable();
            $table->json('assembly_photos')->nullable();
            $table->text('assembly_notes')->nullable();

            // Post-Build Hardware Verification
            $table->boolean('system_powered_on')->nullable();
            $table->boolean('post_successful')->nullable();
            $table->boolean('bios_accessible')->nullable();
            $table->boolean('cpu_detected')->nullable();
            $table->boolean('memory_detected')->nullable();
            $table->boolean('storage_detected')->nullable();
            $table->boolean('graphics_card_detected')->nullable();
            $table->boolean('cpu_cooler_operating')->nullable();
            $table->boolean('case_fans_operating')->nullable();
            $table->boolean('no_abnormal_noise')->nullable();
            $table->json('post_build_hardware_photos')->nullable();
            $table->text('post_build_hardware_notes')->nullable();

            // Post-Build Software Verification
            $table->boolean('windows_boot_successful')->nullable();
            $table->boolean('windows_activation_verified')->nullable();
            $table->boolean('display_output_verified')->nullable();
            $table->boolean('network_connected')->nullable();
            $table->boolean('internet_accessible')->nullable();
            $table->boolean('audio_output_verified')->nullable();
            $table->boolean('usb_ports_verified')->nullable();
            $table->boolean('rgb_lighting_verified')->nullable();
            $table->json('post_build_software_photos')->nullable();
            $table->text('post_build_software_notes')->nullable();

            // Customer Acceptance
            $table->boolean('physical_condition_accepted')->nullable();
            $table->boolean('system_boot_verified')->nullable();
            $table->boolean('display_verified')->nullable();
            $table->boolean('peripherals_verified')->nullable();
            $table->boolean('accessories_received')->nullable();
            $table->boolean('documentation_received')->nullable();
            $table->boolean('customer_demonstration_completed')->nullable();
            $table->text('customer_acceptance_notes')->nullable();

            // Acknowledgement
            $table->string('customer_ack_name')->nullable();
            $table->boolean('customer_acknowledged')->nullable();
            $table->string('technician_ack_name')->nullable();
            $table->boolean('technician_acknowledged')->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['order_id', 'round']);
            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('onsite_handovers');
    }
}
```

Note: the orders table is named `order` (singular) in this codebase's DB — `app/Models/Order.php` has `protected $table = 'order';`. The foreign key above targets `order`, not `orders`.

- [ ] **Step 2: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnsiteHandover extends Model
{
    use SoftDeletes;

    protected $table = 'onsite_handovers';
    protected $guarded = ['id'];

    protected $casts = [
        'order_id' => 'integer',
        'round' => 'integer',
        'service_date' => 'date',
        'component_serial_numbers_matched' => 'boolean',
        'customer_order_specification_verified' => 'boolean',
        'required_components_present' => 'boolean',
        'required_tools_present' => 'boolean',
        'required_consumables_present' => 'boolean',
        'workspace_available' => 'boolean',
        'adequate_lighting' => 'boolean',
        'stable_work_surface' => 'boolean',
        'sufficient_working_space' => 'boolean',
        'power_outlet_available' => 'boolean',
        'internet_available' => 'boolean',
        'customer_present_at_arrival' => 'boolean',
        'assembly_area_approved_by_customer' => 'boolean',
        'arrival_photos' => 'array',
        'security_seal_intact' => 'boolean',
        'no_signs_of_transit_damage' => 'boolean',
        'accessories_present' => 'boolean',
        'documentation_present' => 'boolean',
        'transport_case_photos' => 'array',
        'component_packaging_photos' => 'array',
        'cpu_installed' => 'boolean',
        'memory_installed' => 'boolean',
        'storage_installed' => 'boolean',
        'cpu_cooler_installed' => 'boolean',
        'motherboard_installed' => 'boolean',
        'power_supply_installed' => 'boolean',
        'case_fans_installed' => 'boolean',
        'graphics_card_installed' => 'boolean',
        'cable_management_completed' => 'boolean',
        'assembly_photos' => 'array',
        'system_powered_on' => 'boolean',
        'post_successful' => 'boolean',
        'bios_accessible' => 'boolean',
        'cpu_detected' => 'boolean',
        'memory_detected' => 'boolean',
        'storage_detected' => 'boolean',
        'graphics_card_detected' => 'boolean',
        'cpu_cooler_operating' => 'boolean',
        'case_fans_operating' => 'boolean',
        'no_abnormal_noise' => 'boolean',
        'post_build_hardware_photos' => 'array',
        'windows_boot_successful' => 'boolean',
        'windows_activation_verified' => 'boolean',
        'display_output_verified' => 'boolean',
        'network_connected' => 'boolean',
        'internet_accessible' => 'boolean',
        'audio_output_verified' => 'boolean',
        'usb_ports_verified' => 'boolean',
        'rgb_lighting_verified' => 'boolean',
        'post_build_software_photos' => 'array',
        'physical_condition_accepted' => 'boolean',
        'system_boot_verified' => 'boolean',
        'display_verified' => 'boolean',
        'peripherals_verified' => 'boolean',
        'accessories_received' => 'boolean',
        'documentation_received' => 'boolean',
        'customer_demonstration_completed' => 'boolean',
        'customer_acknowledged' => 'boolean',
        'technician_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
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

Run: `php -l database/migrations/2026_07_27_200000_create_onsite_handovers_table.php` and `php -l app/Models/OnsiteHandover.php`. Expected: `No syntax errors detected` for each. If no `php` binary is available in this environment, do a manual brace/paren-balance check instead (established fallback from prior plans).

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_07_27_200000_create_onsite_handovers_table.php app/Models/OnsiteHandover.php
git commit -m "Add onsite_handovers migration and model"
```

---

### Task 2: Controller Part 1 — show(), report ID generation, and 4 simple sections

**Files:**
- Create: `app/Http/Controllers/OnsiteHandoverController.php`

**Interfaces:**
- Consumes: `OnsiteHandover` model (Task 1); `Order`, `CraftInspection`, `PerformanceTest`, `ServeData`, `CareData` models (all already exist in this codebase).
- Produces: `OnsiteHandoverController::show()`, `updateReportInfo()`, `updateCustomerInfo()`, `updateBuildInfo()`, `updateStudioDocs()` — Task 6's routes point to these by exact name. This task creates the controller file; Tasks 3-5 add more methods to the same file.

- [ ] **Step 1: Write the controller with field constants, show(), and the report-ID generator**

```php
<?php

namespace App\Http\Controllers;

use App\Models\CareData;
use App\Models\CraftInspection;
use App\Models\OnsiteHandover;
use App\Models\Order;
use App\Models\PerformanceTest;
use App\Models\ServeData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OnsiteHandoverController extends Controller
{
    const REPORT_INFO_FIELDS = [
        'service_date', 'work_start_time', 'work_completion_time',
        'technician_name', 'assistant_technician', 'service_location', 'service_type', 'status',
    ];

    const CUSTOMER_INFO_FIELDS = [
        'customer_present_during_assembly', 'authorised_representative', 'service_address',
    ];

    const BUILD_INFO_FIELDS = [
        'pc_purpose', 'operating_system', 'operating_system_version',
    ];

    const STUDIO_DOCS_FIELDS = [
        'component_serial_numbers_matched', 'customer_order_specification_verified',
        'required_components_present', 'required_tools_present', 'required_consumables_present',
        'studio_docs_notes',
    ];

    public function show($orderId, $round = 1)
    {
        $order = Order::with(['customer', 'craft'])->find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            $handover = OnsiteHandover::create([
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
                'onsite_handover' => $handover,
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
        $nextId = OnsiteHandover::count() + 1;

        return 'OSH-QVCT-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
```

- [ ] **Step 2: Add `updateReportInfo()`**

```php
    public function updateReportInfo(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'service_date' => 'nullable|date',
            'work_start_time' => 'nullable|string|max:255',
            'work_completion_time' => 'nullable|string|max:255',
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

- [ ] **Step 3: Add `updateCustomerInfo()`**

```php
    public function updateCustomerInfo(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_present_during_assembly' => 'nullable|in:yes,no,partially',
            'authorised_representative' => 'nullable|string|max:255',
            'service_address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::CUSTOMER_INFO_FIELDS));
            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Customer information updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update customer information', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 4: Add `updateBuildInfo()`**

```php
    public function updateBuildInfo(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'pc_purpose' => 'nullable|string|max:255',
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

- [ ] **Step 5: Add `updateStudioDocs()`**

```php
    public function updateStudioDocs(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'component_serial_numbers_matched' => 'nullable|boolean',
            'customer_order_specification_verified' => 'nullable|boolean',
            'required_components_present' => 'nullable|boolean',
            'required_tools_present' => 'nullable|boolean',
            'required_consumables_present' => 'nullable|boolean',
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

Add this method, then close the class with `}` on its own line after it.

- [ ] **Step 6: Lint the file**

Run: `php -l app/Http/Controllers/OnsiteHandoverController.php`. Expected: `No syntax errors detected`. If unavailable, manually verify brace balance.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/OnsiteHandoverController.php
git commit -m "Add OnsiteHandoverController: show(), report ID generation, Report/Customer/Build Info and Studio Docs sections"
```

---

### Task 3: Controller Part 2 — Arrival and Transportation sections (photo handling)

**Files:**
- Modify: `app/Http/Controllers/OnsiteHandoverController.php`

**Interfaces:**
- Produces: `updateArrival()`, `updateTransportation()`, plus private `storePhotos()`/`mergePhotos()` helpers — Task 6's routes point to the two public methods by exact name; Tasks 4 reuses the same private helpers (no need to re-add them).

- [ ] **Step 1: Add the 2 field constants**

Add directly after `STUDIO_DOCS_FIELDS`:

```php
    const ARRIVAL_FIELDS = [
        'arrival_time', 'service_environment',
        'workspace_available', 'adequate_lighting', 'stable_work_surface', 'sufficient_working_space',
        'power_outlet_available', 'internet_available', 'customer_present_at_arrival', 'assembly_area_approved_by_customer',
        'arrival_notes',
    ];

    const TRANSPORTATION_FIELDS = [
        'transport_case_note', 'transport_case_status',
        'component_packaging_note', 'component_packaging_status',
        'security_seal_intact', 'no_signs_of_transit_damage', 'accessories_present', 'documentation_present',
        'transportation_notes', 'transportation_verdict',
    ];
```

Note: `arrival_time` appears here in `ARRIVAL_FIELDS` and NOT in `REPORT_INFO_FIELDS` (Task 2) — this is deliberate, see Global Constraints' "one column, one writer" rule. Double check `REPORT_INFO_FIELDS` still excludes it before proceeding.

- [ ] **Step 2: Add `updateArrival()`**

Add after `updateStudioDocs()`:

```php
    public function updateArrival(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'arrival_time' => 'nullable|string|max:255',
            'service_environment' => 'nullable|in:residential,office,studio,commercial,other',
            'workspace_available' => 'nullable|boolean',
            'adequate_lighting' => 'nullable|boolean',
            'stable_work_surface' => 'nullable|boolean',
            'sufficient_working_space' => 'nullable|boolean',
            'power_outlet_available' => 'nullable|boolean',
            'internet_available' => 'nullable|boolean',
            'customer_present_at_arrival' => 'nullable|boolean',
            'assembly_area_approved_by_customer' => 'nullable|boolean',
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

- [ ] **Step 3: Add `updateTransportation()`**

```php
    public function updateTransportation(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'transport_case_note' => 'nullable|string|max:255',
            'transport_case_status' => 'nullable|in:sound,damaged',
            'transport_case_photos.*' => 'nullable|image|max:5120',
            'remove_transport_case_photos' => 'nullable|array',
            'component_packaging_note' => 'nullable|string|max:255',
            'component_packaging_status' => 'nullable|in:sound,damaged',
            'component_packaging_photos.*' => 'nullable|image|max:5120',
            'remove_component_packaging_photos' => 'nullable|array',
            'security_seal_intact' => 'nullable|boolean',
            'no_signs_of_transit_damage' => 'nullable|boolean',
            'accessories_present' => 'nullable|boolean',
            'documentation_present' => 'nullable|boolean',
            'transportation_notes' => 'nullable|string|max:1000',
            'transportation_verdict' => 'nullable|in:sound_ready,issue_found',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::TRANSPORTATION_FIELDS));

            if ($request->hasFile('transport_case_photos') || $request->filled('remove_transport_case_photos')) {
                $handover->transport_case_photos = $this->mergePhotos($handover->transport_case_photos, $request, 'transport_case_photos', 'remove_transport_case_photos');
            }
            if ($request->hasFile('component_packaging_photos') || $request->filled('remove_component_packaging_photos')) {
                $handover->component_packaging_photos = $this->mergePhotos($handover->component_packaging_photos, $request, 'component_packaging_photos', 'remove_component_packaging_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Transportation inspection updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update transportation inspection', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 4: Add the private photo helpers**

Add these two methods just before the class's final closing `}`:

```php
    private function storePhotos(Request $request, $field)
    {
        if (!$request->hasFile($field)) {
            return [];
        }

        $paths = [];
        foreach ($request->file($field) as $file) {
            $paths[] = $file->store('onsite-handovers', 'public');
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

Note the storage subdirectory is `'onsite-handovers'`, distinct from `PerformanceTestController`'s `'performance-tests'` — each feature keeps its uploads in its own folder.

- [ ] **Step 5: Lint the file**

Run: `php -l app/Http/Controllers/OnsiteHandoverController.php`. Expected: `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/OnsiteHandoverController.php
git commit -m "Add Arrival and Transportation sections with photo upload support"
```

---

### Task 4: Controller Part 3 — Assembly, Post-Build Hardware, Post-Build Software sections

**Files:**
- Modify: `app/Http/Controllers/OnsiteHandoverController.php`

**Interfaces:**
- Consumes: private `mergePhotos()` helper added in Task 3 — do not re-add it.
- Produces: `updateAssembly()`, `updatePostBuildHardware()`, `updatePostBuildSoftware()`.

- [ ] **Step 1: Add the 3 field constants**

Add directly after `TRANSPORTATION_FIELDS`:

```php
    const ASSEMBLY_FIELDS = [
        'cpu_installed', 'memory_installed', 'storage_installed', 'cpu_cooler_installed', 'motherboard_installed',
        'power_supply_installed', 'case_fans_installed', 'graphics_card_installed', 'cable_management_completed',
        'assembly_notes',
    ];

    const POST_BUILD_HARDWARE_FIELDS = [
        'system_powered_on', 'post_successful', 'bios_accessible', 'cpu_detected', 'memory_detected',
        'storage_detected', 'graphics_card_detected', 'cpu_cooler_operating', 'case_fans_operating', 'no_abnormal_noise',
        'post_build_hardware_notes',
    ];

    const POST_BUILD_SOFTWARE_FIELDS = [
        'windows_boot_successful', 'windows_activation_verified', 'display_output_verified', 'network_connected',
        'internet_accessible', 'audio_output_verified', 'usb_ports_verified', 'rgb_lighting_verified',
        'post_build_software_notes',
    ];
```

- [ ] **Step 2: Add `updateAssembly()`**

Add after `updateTransportation()`:

```php
    public function updateAssembly(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cpu_installed' => 'nullable|boolean',
            'memory_installed' => 'nullable|boolean',
            'storage_installed' => 'nullable|boolean',
            'cpu_cooler_installed' => 'nullable|boolean',
            'motherboard_installed' => 'nullable|boolean',
            'power_supply_installed' => 'nullable|boolean',
            'case_fans_installed' => 'nullable|boolean',
            'graphics_card_installed' => 'nullable|boolean',
            'cable_management_completed' => 'nullable|boolean',
            'assembly_notes' => 'nullable|string|max:1000',
            'assembly_photos.*' => 'nullable|image|max:5120',
            'remove_assembly_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::ASSEMBLY_FIELDS));

            if ($request->hasFile('assembly_photos') || $request->filled('remove_assembly_photos')) {
                $handover->assembly_photos = $this->mergePhotos($handover->assembly_photos, $request, 'assembly_photos', 'remove_assembly_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Assembly updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update assembly', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 3: Add `updatePostBuildHardware()`**

```php
    public function updatePostBuildHardware(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'system_powered_on' => 'nullable|boolean',
            'post_successful' => 'nullable|boolean',
            'bios_accessible' => 'nullable|boolean',
            'cpu_detected' => 'nullable|boolean',
            'memory_detected' => 'nullable|boolean',
            'storage_detected' => 'nullable|boolean',
            'graphics_card_detected' => 'nullable|boolean',
            'cpu_cooler_operating' => 'nullable|boolean',
            'case_fans_operating' => 'nullable|boolean',
            'no_abnormal_noise' => 'nullable|boolean',
            'post_build_hardware_notes' => 'nullable|string|max:1000',
            'post_build_hardware_photos.*' => 'nullable|image|max:5120',
            'remove_post_build_hardware_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::POST_BUILD_HARDWARE_FIELDS));

            if ($request->hasFile('post_build_hardware_photos') || $request->filled('remove_post_build_hardware_photos')) {
                $handover->post_build_hardware_photos = $this->mergePhotos($handover->post_build_hardware_photos, $request, 'post_build_hardware_photos', 'remove_post_build_hardware_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Post-build hardware verification updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update post-build hardware verification', 'error' => $e->getMessage()], 500);
        }
    }
```

- [ ] **Step 4: Add `updatePostBuildSoftware()`**

```php
    public function updatePostBuildSoftware(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'windows_boot_successful' => 'nullable|boolean',
            'windows_activation_verified' => 'nullable|boolean',
            'display_output_verified' => 'nullable|boolean',
            'network_connected' => 'nullable|boolean',
            'internet_accessible' => 'nullable|boolean',
            'audio_output_verified' => 'nullable|boolean',
            'usb_ports_verified' => 'nullable|boolean',
            'rgb_lighting_verified' => 'nullable|boolean',
            'post_build_software_notes' => 'nullable|string|max:1000',
            'post_build_software_photos.*' => 'nullable|image|max:5120',
            'remove_post_build_software_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::POST_BUILD_SOFTWARE_FIELDS));

            if ($request->hasFile('post_build_software_photos') || $request->filled('remove_post_build_software_photos')) {
                $handover->post_build_software_photos = $this->mergePhotos($handover->post_build_software_photos, $request, 'post_build_software_photos', 'remove_post_build_software_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Post-build software verification updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update post-build software verification', 'error' => $e->getMessage()], 500);
        }
    }
```

Place these 3 new methods before the private `storePhotos()`/`mergePhotos()` helpers Task 3 added (methods can be in any order in a PHP class, but keep public update methods grouped together for readability, matching `PerformanceTestController`'s layout).

- [ ] **Step 5: Lint the file**

Run: `php -l app/Http/Controllers/OnsiteHandoverController.php`. Expected: `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/OnsiteHandoverController.php
git commit -m "Add Assembly, Post-Build Hardware, and Post-Build Software sections"
```

---

### Task 5: Controller Part 4 — Customer Acceptance and Acknowledgement sections

**Files:**
- Modify: `app/Http/Controllers/OnsiteHandoverController.php`

**Interfaces:**
- Produces: `updateCustomerAcceptance()`, `updateAcknowledgement()` — the last 2 of the 11 section endpoints.

- [ ] **Step 1: Add the 2 field constants**

Add directly after `POST_BUILD_SOFTWARE_FIELDS`:

```php
    const CUSTOMER_ACCEPTANCE_FIELDS = [
        'physical_condition_accepted', 'system_boot_verified', 'display_verified', 'peripherals_verified',
        'accessories_received', 'documentation_received', 'customer_demonstration_completed',
        'customer_acceptance_notes',
    ];

    const ACKNOWLEDGEMENT_FIELDS = [
        'customer_ack_name', 'customer_acknowledged', 'technician_ack_name', 'technician_acknowledged',
    ];
```

- [ ] **Step 2: Add `updateCustomerAcceptance()`**

Add after `updatePostBuildSoftware()`:

```php
    public function updateCustomerAcceptance(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'physical_condition_accepted' => 'nullable|boolean',
            'system_boot_verified' => 'nullable|boolean',
            'display_verified' => 'nullable|boolean',
            'peripherals_verified' => 'nullable|boolean',
            'accessories_received' => 'nullable|boolean',
            'documentation_received' => 'nullable|boolean',
            'customer_demonstration_completed' => 'nullable|boolean',
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

- [ ] **Step 3: Add `updateAcknowledgement()`**

```php
    public function updateAcknowledgement(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_ack_name' => 'nullable|string|max:255',
            'customer_acknowledged' => 'nullable|boolean',
            'technician_ack_name' => 'nullable|string|max:255',
            'technician_acknowledged' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::ACKNOWLEDGEMENT_FIELDS));

            if ($handover->customer_acknowledged && $handover->technician_acknowledged && !$handover->acknowledged_at) {
                $handover->acknowledged_at = now();
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Acknowledgement updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update acknowledgement', 'error' => $e->getMessage()], 500);
        }
    }
```

`acknowledged_at` is set server-side only, the first time both flags are simultaneously true — it is never accepted from the request directly (not in `ACKNOWLEDGEMENT_FIELDS`).

- [ ] **Step 4: Lint the file**

Run: `php -l app/Http/Controllers/OnsiteHandoverController.php`. Expected: `No syntax errors detected`. This is the last controller task — also do a full read-through confirming all 11 public update methods plus `show()` plus the 2 private helpers exist exactly once each (no duplicates from copy/paste across Tasks 2-5).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/OnsiteHandoverController.php
git commit -m "Add Customer Acceptance and Acknowledgement sections"
```

---

### Task 6: Routes (backend API + frontend router)

**Files:**
- Modify: `routes/api.php`
- Modify: `resources/js/routes.js`

**Interfaces:**
- Consumes: all 12 `OnsiteHandoverController` methods (Tasks 2-5) by exact name.
- Produces: the `order/{orderId}/onsite-handover/{round}` API prefix and the `onsitehandover` frontend route name — Task 11's `index.vue` and Task 12's quick-launch button both depend on this route name.

- [ ] **Step 1: Add the API route group**

In `routes/api.php`, find the closing of the Performance Testing route group:

```php
Route::prefix('order/{orderId}/performance-test/{round}')->group(function () {
    Route::get('/', 'PerformanceTestController@show');
    Route::post('/', 'PerformanceTestController@update');
    Route::post('/items/{itemId}', 'PerformanceTestController@updateItem');
    Route::post('/cpu-results', 'PerformanceTestController@updateCpuResults');
    Route::post('/gpu-results', 'PerformanceTestController@updateGpuResults');
    Route::post('/system-stability-results', 'PerformanceTestController@updateSystemStabilityResults');
    Route::post('/memory-results', 'PerformanceTestController@updateMemoryResults');
    Route::post('/storage-results', 'PerformanceTestController@updateStorageResults');
    Route::post('/cooling-performance-results', 'PerformanceTestController@updateCoolingPerformanceResults');
    Route::post('/cooling-system-results', 'PerformanceTestController@updateCoolingSystemResults');
    Route::post('/display-results', 'PerformanceTestController@updateDisplayResults');
    Route::post('/network-results', 'PerformanceTestController@updateNetworkResults');
    Route::post('/usb-results', 'PerformanceTestController@updateUsbResults');
    Route::post('/usb-ports', 'PerformanceTestController@storeUsbPort');
    Route::post('/usb-ports/{itemId}', 'PerformanceTestController@updateUsbPort');
    Route::delete('/usb-ports/{itemId}', 'PerformanceTestController@destroyUsbPort');
    Route::post('/complete', 'PerformanceTestController@complete');
});
```

Add immediately after this block's closing `});`:

```php

/*
|--------------------------------------------------------------------------
| ONSITE HANDOVER (QuiviCraft)
|--------------------------------------------------------------------------
*/
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

- [ ] **Step 2: Register the frontend route**

In `resources/js/routes.js`, find:

```js
let craftinspection = require('./components/craft_inspection/index.vue').default;
let performancetest = require('./components/performance_test/index.vue').default;
```

Replace with:

```js
let craftinspection = require('./components/craft_inspection/index.vue').default;
let performancetest = require('./components/performance_test/index.vue').default;
let onsitehandover = require('./components/onsite_handover/index.vue').default;
```

Then find:

```js
      { path: '/order/:id/inspection/:round', component: craftinspection, name: 'craftinspection', meta: { layout: 'app' } },
      { path: '/order/:id/performance-test/:round', component: performancetest, name: 'performancetest', meta: { layout: 'app' } },
```

Replace with:

```js
      { path: '/order/:id/inspection/:round', component: craftinspection, name: 'craftinspection', meta: { layout: 'app' } },
      { path: '/order/:id/performance-test/:round', component: performancetest, name: 'performancetest', meta: { layout: 'app' } },
      { path: '/order/:id/onsite-handover/:round', component: onsitehandover, name: 'onsitehandover', meta: { layout: 'app' } },
```

Note: `resources/js/components/onsite_handover/index.vue` does not exist yet — it's created in Task 11. This `require()` will not resolve until then; that's fine, this task's job is only to register the route/import line, not to make the app compile standalone (the frontend bundle isn't rebuilt until Task 11 lands and `index.vue` exists — earlier tasks in this plan don't touch the frontend build at all, so there's no broken intermediate compile state to worry about).

- [ ] **Step 3: Lint both files**

Run: `php -l routes/api.php`. For `routes.js`, do a manual read-through confirming the `require`/route-array syntax is well-formed (no Node available to lint JS in this environment).

- [ ] **Step 4: Commit**

```bash
git add routes/api.php resources/js/routes.js
git commit -m "Wire OnSite Handover routes (backend API + frontend router)"
```

---

### Task 7: Frontend — shared `PhotoUploadField.vue` component

**Files:**
- Create: `resources/js/components/shared/PhotoUploadField.vue`

**Interfaces:**
- Produces: a reusable photo-upload widget — Tasks 9 and 10's photo-bearing sections (Arrival, Transportation, Assembly, Post-Build Hardware, Post-Build Software) all mount this component, passing `existing-photos`/`new-photos` and listening for `add-photos`/`remove-existing`/`remove-new`.

This generalizes `performance_test/index.vue`'s inline `PhotoNoteField` local component (which is hardcoded to a 2-photo cap and bundles in a notes textarea) into a standalone, uncapped, photo-only component — this feature's sections already have their own separate notes textareas, so only the photo-grid part is needed here, and the doc doesn't specify a photo-count cap the way Craft Inspection's pass/fail rule does.

- [ ] **Step 1: Write the component**

```vue
<template>
  <div class="photo-upload-field">
    <label class="small text-muted mb-1">Photos (optional)</label>
    <div class="d-flex flex-wrap align-items-center">
      <div v-for="path in existingPhotos" :key="path" class="photo-thumb">
        <img :src="'/storage/' + path" alt="photo">
        <button type="button" class="remove-btn" @click="$emit('remove-existing', path)">&times;</button>
      </div>
      <div v-for="(file, idx) in newPhotos" :key="'new-' + idx" class="photo-thumb">
        <img :src="fileUrl(file)" alt="new photo">
        <button type="button" class="remove-btn" @click="$emit('remove-new', idx)">&times;</button>
      </div>
      <div class="photo-upload-btn">
        <input type="file" accept="image/*" multiple @change="onFileChange">
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PhotoUploadField',
  props: {
    existingPhotos: { type: Array, default: () => [] },
    newPhotos: { type: Array, default: () => [] },
  },
  methods: {
    fileUrl(file) {
      return URL.createObjectURL(file);
    },
    onFileChange(event) {
      if (event.target.files && event.target.files.length) {
        this.$emit('add-photos', event.target.files);
      }
      event.target.value = '';
    },
  },
};
</script>

<style scoped>
.photo-thumb {
  position: relative;
  width: 80px;
  height: 80px;
  margin: 0 8px 8px 0;
}
.photo-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 4px;
}
.photo-thumb .remove-btn {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #dc3545;
  color: #fff;
  border: none;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  line-height: 18px;
  font-size: 12px;
  cursor: pointer;
  padding: 0;
}
.photo-upload-btn input[type=file] {
  width: 180px;
}
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/shared/PhotoUploadField.vue
git commit -m "Add shared PhotoUploadField.vue component"
```

---

### Task 8: Frontend — Report Info, Customer Info, Build Info, Studio Docs sections

**Files:**
- Create: `resources/js/components/onsite_handover/ReportInfoSection.vue`
- Create: `resources/js/components/onsite_handover/CustomerInfoSection.vue`
- Create: `resources/js/components/onsite_handover/BuildInfoSection.vue`
- Create: `resources/js/components/onsite_handover/StudioDocsSection.vue`

**Interfaces:**
- Consumes: `POST {apiBase}/report-info`, `.../customer-info`, `.../build-info`, `.../studio-docs` (all from Task 2).
- Produces: each emits `saved` with the response `data` — Task 11's `index.vue` wiring depends on this.
- `CustomerInfoSection` additionally takes read-only display props `customerName`/`customerId`/`contactNumber`/`emailAddress` (String, nullable, default `null`) — sourced from the order's `customer` relation (already eager-loaded by `show()`), NOT part of its `FIELD_KEYS`/`save()`, purely informational.
- `BuildInfoSection` additionally takes read-only display props `quivicraftId`/`quivicraftPlan`/`quiviserveId`/`quiviserveCustomerId`/`quiviservePlan`/`quivicareId`/`quivicarePlan` (String, nullable, default `null`) — these are NOT part of its `FIELD_KEYS`/`save()`, purely informational.
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
            <label class="form-label">Service Date</label>
            <input type="date" class="form-control" v-model="form.service_date">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Work Start Time</label>
            <input type="time" class="form-control" v-model="form.work_start_time">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Work Completion Time</label>
            <input type="time" class="form-control" v-model="form.work_completion_time">
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
  'service_date', 'work_start_time', 'work_completion_time',
  'technician_name', 'assistant_technician', 'service_location', 'service_type', 'status',
];

const BOOLEAN_KEYS = [];

const STRING_KEYS = [
  'service_date', 'work_start_time', 'work_completion_time',
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

- [ ] **Step 2: Write `CustomerInfoSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Customer Information</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Looked up from this order (read-only)</h6>
      <div class="row">
        <div class="col-md-3"><small class="text-muted d-block">Customer Name</small>{{ customerName || '—' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">Customer ID</small>{{ customerId || '—' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">Contact Number</small>{{ contactNumber || '—' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">Email Address</small>{{ emailAddress || '—' }}</div>
      </div>

      <h6 class="text-muted mt-3">Technician-entered</h6>
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Customer Present During Assembly</label>
            <select class="form-control" v-model="form.customer_present_during_assembly">
              <option value="">Not selected</option>
              <option value="yes">Yes</option>
              <option value="no">No</option>
              <option value="partially">Partially</option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Authorised Representative</label>
            <input type="text" class="form-control" v-model="form.authorised_representative">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Service Address</label>
            <input type="text" class="form-control" v-model="form.service_address">
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
          Save Customer Information
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = ['customer_present_during_assembly', 'authorised_representative', 'service_address'];
const BOOLEAN_KEYS = [];
const STRING_KEYS = ['customer_present_during_assembly', 'authorised_representative', 'service_address'];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
    customerName: { type: String, default: null },
    customerId: { type: String, default: null },
    contactNumber: { type: String, default: null },
    emailAddress: { type: String, default: null },
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
        const res = await axios.post(`${this.apiBase}/customer-info`, payload);
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Customer information updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save customer information'];
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

- [ ] **Step 3: Write `BuildInfoSection.vue`**

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
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">PC Purpose</label>
            <input type="text" class="form-control" v-model="form.pc_purpose">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label class="form-label">Operating System</label>
            <input type="text" class="form-control" v-model="form.operating_system">
          </div>
        </div>
        <div class="col-md-4">
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

const FIELD_KEYS = ['pc_purpose', 'operating_system', 'operating_system_version'];
const BOOLEAN_KEYS = [];
const STRING_KEYS = ['pc_purpose', 'operating_system', 'operating_system_version'];

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

- [ ] **Step 4: Write `StudioDocsSection.vue`**

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
      <div class="row">
        <div class="col-md-4" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'sd-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'sd-' + f.key">{{ f.label }}</label>
          </div>
        </div>
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

const FIELD_KEYS = [
  'component_serial_numbers_matched', 'customer_order_specification_verified',
  'required_components_present', 'required_tools_present', 'required_consumables_present',
  'studio_docs_notes',
];

const BOOLEAN_KEYS = [
  'component_serial_numbers_matched', 'customer_order_specification_verified',
  'required_components_present', 'required_tools_present', 'required_consumables_present',
];

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
      checklistFields: [
        { key: 'component_serial_numbers_matched', label: 'Component Serial Numbers Matched' },
        { key: 'customer_order_specification_verified', label: 'Customer Order Specification Verified' },
        { key: 'required_components_present', label: 'Required Components Present' },
        { key: 'required_tools_present', label: 'Required Tools Present' },
        { key: 'required_consumables_present', label: 'Required Consumables Present' },
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

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/onsite_handover/ReportInfoSection.vue resources/js/components/onsite_handover/CustomerInfoSection.vue resources/js/components/onsite_handover/BuildInfoSection.vue resources/js/components/onsite_handover/StudioDocsSection.vue
git commit -m "Add Report Info, Customer Info, Build Info, and Studio Docs section components"
```

---

### Task 9: Frontend — Arrival and Transportation sections (with photos)

**Files:**
- Create: `resources/js/components/onsite_handover/ArrivalSection.vue`
- Create: `resources/js/components/onsite_handover/TransportationSection.vue`

**Interfaces:**
- Consumes: `PhotoUploadField` (Task 7); `POST {apiBase}/arrival`, `.../transportation` (Task 3).
- Produces: each emits `saved` with the response `data`.

- [ ] **Step 1: Write `ArrivalSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">On-Site Arrival Verification</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Arrival Time</label>
            <input type="time" class="form-control" v-model="form.arrival_time">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label class="form-label">Service Environment</label>
            <select class="form-control" v-model="form.service_environment">
              <option value="">Not selected</option>
              <option value="residential">Residential</option>
              <option value="office">Office</option>
              <option value="studio">Studio</option>
              <option value="commercial">Commercial</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
      </div>

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
  'arrival_time', 'service_environment',
  'workspace_available', 'adequate_lighting', 'stable_work_surface', 'sufficient_working_space',
  'power_outlet_available', 'internet_available', 'customer_present_at_arrival', 'assembly_area_approved_by_customer',
  'arrival_notes',
];

const BOOLEAN_KEYS = [
  'workspace_available', 'adequate_lighting', 'stable_work_surface', 'sufficient_working_space',
  'power_outlet_available', 'internet_available', 'customer_present_at_arrival', 'assembly_area_approved_by_customer',
];

const STRING_KEYS = ['arrival_time', 'service_environment', 'arrival_notes'];

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
        { key: 'adequate_lighting', label: 'Adequate Lighting' },
        { key: 'stable_work_surface', label: 'Stable Work Surface' },
        { key: 'sufficient_working_space', label: 'Sufficient Working Space' },
        { key: 'power_outlet_available', label: 'Power Outlet Available' },
        { key: 'internet_available', label: 'Internet Available (Optional)' },
        { key: 'customer_present_at_arrival', label: 'Customer Present' },
        { key: 'assembly_area_approved_by_customer', label: 'Assembly Area Approved by Customer' },
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

- [ ] **Step 2: Write `TransportationSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Transportation Inspection</h5></div>
    <div class="card-body">
      <h6 class="text-muted">Transport Case Condition</h6>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label class="form-label">Notes</label>
            <input type="text" class="form-control" v-model="form.transport_case_note">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="form-label">Condition</label>
            <select class="form-control" v-model="form.transport_case_status">
              <option value="">Not selected</option>
              <option value="sound">Sound</option>
              <option value="damaged">Damaged</option>
            </select>
          </div>
        </div>
      </div>
      <photo-upload-field
        :existing-photos="existingTransportCasePhotos"
        :new-photos="newTransportCasePhotos"
        @add-photos="addTransportCasePhotos"
        @remove-existing="removeExistingTransportCasePhoto"
        @remove-new="removeNewTransportCasePhoto"
      />

      <h6 class="text-muted mt-3">Component Packaging Condition</h6>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label class="form-label">Notes</label>
            <input type="text" class="form-control" v-model="form.component_packaging_note">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="form-label">Condition</label>
            <select class="form-control" v-model="form.component_packaging_status">
              <option value="">Not selected</option>
              <option value="sound">Sound</option>
              <option value="damaged">Damaged</option>
            </select>
          </div>
        </div>
      </div>
      <photo-upload-field
        :existing-photos="existingComponentPackagingPhotos"
        :new-photos="newComponentPackagingPhotos"
        @add-photos="addComponentPackagingPhotos"
        @remove-existing="removeExistingComponentPackagingPhoto"
        @remove-new="removeNewComponentPackagingPhoto"
      />

      <h6 class="text-muted mt-3">Checks</h6>
      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'trans-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'trans-' + f.key">{{ f.label }}</label>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-8">
          <div class="form-group mt-2">
            <label class="form-label">Notes</label>
            <textarea class="form-control" rows="2" v-model="form.transportation_notes"></textarea>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group mt-2">
            <label class="form-label">Verdict</label>
            <select class="form-control" v-model="form.transportation_verdict">
              <option value="">Not selected</option>
              <option value="sound_ready">Sound &amp; Ready for Assembly</option>
              <option value="issue_found">Issue Found</option>
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
          Save Transportation Inspection
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
  'transport_case_note', 'transport_case_status',
  'component_packaging_note', 'component_packaging_status',
  'security_seal_intact', 'no_signs_of_transit_damage', 'accessories_present', 'documentation_present',
  'transportation_notes', 'transportation_verdict',
];

const BOOLEAN_KEYS = ['security_seal_intact', 'no_signs_of_transit_damage', 'accessories_present', 'documentation_present'];

const STRING_KEYS = [
  'transport_case_note', 'transport_case_status', 'component_packaging_note', 'component_packaging_status',
  'transportation_notes', 'transportation_verdict',
];

export default {
  components: { PhotoUploadField },
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      existingTransportCasePhotos: this.initialData.transport_case_photos || [],
      newTransportCasePhotos: [],
      removeTransportCasePhotos: [],
      existingComponentPackagingPhotos: this.initialData.component_packaging_photos || [],
      newComponentPackagingPhotos: [],
      removeComponentPackagingPhotos: [],
      saving: false,
      errors: [],
      checklistFields: [
        { key: 'security_seal_intact', label: 'QuiviTech Security Seal Intact' },
        { key: 'no_signs_of_transit_damage', label: 'No Signs of Transit Damage' },
        { key: 'accessories_present', label: 'Accessories Present' },
        { key: 'documentation_present', label: 'Documentation Present' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.existingTransportCasePhotos = newVal.transport_case_photos || [];
      this.newTransportCasePhotos = [];
      this.removeTransportCasePhotos = [];
      this.existingComponentPackagingPhotos = newVal.component_packaging_photos || [];
      this.newComponentPackagingPhotos = [];
      this.removeComponentPackagingPhotos = [];
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
    addTransportCasePhotos(files) {
      Array.from(files).forEach(f => this.newTransportCasePhotos.push(f));
    },
    removeExistingTransportCasePhoto(path) {
      this.existingTransportCasePhotos = this.existingTransportCasePhotos.filter(p => p !== path);
      this.removeTransportCasePhotos.push(path);
    },
    removeNewTransportCasePhoto(idx) {
      this.newTransportCasePhotos.splice(idx, 1);
    },
    addComponentPackagingPhotos(files) {
      Array.from(files).forEach(f => this.newComponentPackagingPhotos.push(f));
    },
    removeExistingComponentPackagingPhoto(path) {
      this.existingComponentPackagingPhotos = this.existingComponentPackagingPhotos.filter(p => p !== path);
      this.removeComponentPackagingPhotos.push(path);
    },
    removeNewComponentPackagingPhoto(idx) {
      this.newComponentPackagingPhotos.splice(idx, 1);
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
      this.newTransportCasePhotos.forEach(f => formData.append('transport_case_photos[]', f));
      this.removeTransportCasePhotos.forEach(p => formData.append('remove_transport_case_photos[]', p));
      this.newComponentPackagingPhotos.forEach(f => formData.append('component_packaging_photos[]', f));
      this.removeComponentPackagingPhotos.forEach(p => formData.append('remove_component_packaging_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/transportation`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.newTransportCasePhotos = [];
        this.removeTransportCasePhotos = [];
        this.newComponentPackagingPhotos = [];
        this.removeComponentPackagingPhotos = [];
        this.existingTransportCasePhotos = res.data.data.transport_case_photos || [];
        this.existingComponentPackagingPhotos = res.data.data.component_packaging_photos || [];
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Transportation inspection updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save transportation inspection'];
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

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/onsite_handover/ArrivalSection.vue resources/js/components/onsite_handover/TransportationSection.vue
git commit -m "Add Arrival and Transportation section components"
```

---

### Task 10: Frontend — Assembly, Post-Build Hardware, Post-Build Software sections

**Files:**
- Create: `resources/js/components/onsite_handover/AssemblySection.vue`
- Create: `resources/js/components/onsite_handover/PostBuildHardwareSection.vue`
- Create: `resources/js/components/onsite_handover/PostBuildSoftwareSection.vue`

**Interfaces:**
- Consumes: `PhotoUploadField` (Task 7); `POST {apiBase}/assembly`, `.../post-build-hardware`, `.../post-build-software` (Task 4).
- Produces: each emits `saved` with the response `data`. All 3 follow the exact single-photo-field pattern `ArrivalSection.vue` (Task 9) established — only the checklist labels, field names, and endpoint path differ.

- [ ] **Step 1: Write `AssemblySection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">QuiviCraft Assembly</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'asm-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'asm-' + f.key">{{ f.label }}</label>
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
        <textarea class="form-control" rows="2" v-model="form.assembly_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Assembly
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
  'cpu_installed', 'memory_installed', 'storage_installed', 'cpu_cooler_installed', 'motherboard_installed',
  'power_supply_installed', 'case_fans_installed', 'graphics_card_installed', 'cable_management_completed',
  'assembly_notes',
];

const BOOLEAN_KEYS = [
  'cpu_installed', 'memory_installed', 'storage_installed', 'cpu_cooler_installed', 'motherboard_installed',
  'power_supply_installed', 'case_fans_installed', 'graphics_card_installed', 'cable_management_completed',
];

const STRING_KEYS = ['assembly_notes'];

export default {
  components: { PhotoUploadField },
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      existingPhotos: this.initialData.assembly_photos || [],
      newPhotos: [],
      removePhotos: [],
      saving: false,
      errors: [],
      checklistFields: [
        { key: 'cpu_installed', label: 'CPU Installed' },
        { key: 'memory_installed', label: 'Memory Installed' },
        { key: 'storage_installed', label: 'Storage Installed' },
        { key: 'cpu_cooler_installed', label: 'CPU Cooler Installed' },
        { key: 'motherboard_installed', label: 'Motherboard Installed' },
        { key: 'power_supply_installed', label: 'Power Supply Installed' },
        { key: 'case_fans_installed', label: 'Case Fans Installed' },
        { key: 'graphics_card_installed', label: 'Graphics Card Installed' },
        { key: 'cable_management_completed', label: 'Cable Management Completed' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.existingPhotos = newVal.assembly_photos || [];
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
      this.newPhotos.forEach(f => formData.append('assembly_photos[]', f));
      this.removePhotos.forEach(p => formData.append('remove_assembly_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/assembly`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.newPhotos = [];
        this.removePhotos = [];
        this.existingPhotos = res.data.data.assembly_photos || [];
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Assembly updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save assembly'];
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

- [ ] **Step 2: Write `PostBuildHardwareSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Post-Build Hardware Verification</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'pbh-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'pbh-' + f.key">{{ f.label }}</label>
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
        <textarea class="form-control" rows="2" v-model="form.post_build_hardware_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Post-Build Hardware Verification
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
  'system_powered_on', 'post_successful', 'bios_accessible', 'cpu_detected', 'memory_detected',
  'storage_detected', 'graphics_card_detected', 'cpu_cooler_operating', 'case_fans_operating', 'no_abnormal_noise',
  'post_build_hardware_notes',
];

const BOOLEAN_KEYS = [
  'system_powered_on', 'post_successful', 'bios_accessible', 'cpu_detected', 'memory_detected',
  'storage_detected', 'graphics_card_detected', 'cpu_cooler_operating', 'case_fans_operating', 'no_abnormal_noise',
];

const STRING_KEYS = ['post_build_hardware_notes'];

export default {
  components: { PhotoUploadField },
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      existingPhotos: this.initialData.post_build_hardware_photos || [],
      newPhotos: [],
      removePhotos: [],
      saving: false,
      errors: [],
      checklistFields: [
        { key: 'system_powered_on', label: 'System Powered On' },
        { key: 'post_successful', label: 'POST Successful' },
        { key: 'bios_accessible', label: 'BIOS Accessible' },
        { key: 'cpu_detected', label: 'CPU Detected' },
        { key: 'memory_detected', label: 'Memory Detected' },
        { key: 'storage_detected', label: 'Storage Detected' },
        { key: 'graphics_card_detected', label: 'Graphics Card Detected' },
        { key: 'cpu_cooler_operating', label: 'CPU Cooler Operating' },
        { key: 'case_fans_operating', label: 'Case Fans Operating' },
        { key: 'no_abnormal_noise', label: 'No Abnormal Noise' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.existingPhotos = newVal.post_build_hardware_photos || [];
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
      this.newPhotos.forEach(f => formData.append('post_build_hardware_photos[]', f));
      this.removePhotos.forEach(p => formData.append('remove_post_build_hardware_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/post-build-hardware`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.newPhotos = [];
        this.removePhotos = [];
        this.existingPhotos = res.data.data.post_build_hardware_photos || [];
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Post-build hardware verification updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save post-build hardware verification'];
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

- [ ] **Step 3: Write `PostBuildSoftwareSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Post-Build Software Verification</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3" v-for="f in checklistFields" :key="f.key">
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" :id="'pbs-' + f.key" v-model="form[f.key]">
            <label class="custom-control-label" :for="'pbs-' + f.key">{{ f.label }}</label>
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
        <textarea class="form-control" rows="2" v-model="form.post_build_software_notes"></textarea>
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Post-Build Software Verification
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
  'windows_boot_successful', 'windows_activation_verified', 'display_output_verified', 'network_connected',
  'internet_accessible', 'audio_output_verified', 'usb_ports_verified', 'rgb_lighting_verified',
  'post_build_software_notes',
];

const BOOLEAN_KEYS = [
  'windows_boot_successful', 'windows_activation_verified', 'display_output_verified', 'network_connected',
  'internet_accessible', 'audio_output_verified', 'usb_ports_verified', 'rgb_lighting_verified',
];

const STRING_KEYS = ['post_build_software_notes'];

export default {
  components: { PhotoUploadField },
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      existingPhotos: this.initialData.post_build_software_photos || [],
      newPhotos: [],
      removePhotos: [],
      saving: false,
      errors: [],
      checklistFields: [
        { key: 'windows_boot_successful', label: 'Windows Boot Successful' },
        { key: 'windows_activation_verified', label: 'Windows Activation Verified' },
        { key: 'display_output_verified', label: 'Display Output Verified' },
        { key: 'network_connected', label: 'Network Connected' },
        { key: 'internet_accessible', label: 'Internet Accessible' },
        { key: 'audio_output_verified', label: 'Audio Output Verified' },
        { key: 'usb_ports_verified', label: 'USB Ports Verified' },
        { key: 'rgb_lighting_verified', label: 'RGB Lighting Verified' },
      ],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.existingPhotos = newVal.post_build_software_photos || [];
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
      this.newPhotos.forEach(f => formData.append('post_build_software_photos[]', f));
      this.removePhotos.forEach(p => formData.append('remove_post_build_software_photos[]', p));

      try {
        const res = await axios.post(`${this.apiBase}/post-build-software`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        this.newPhotos = [];
        this.removePhotos = [];
        this.existingPhotos = res.data.data.post_build_software_photos || [];
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Post-build software verification updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save post-build software verification'];
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
git add resources/js/components/onsite_handover/AssemblySection.vue resources/js/components/onsite_handover/PostBuildHardwareSection.vue resources/js/components/onsite_handover/PostBuildSoftwareSection.vue
git commit -m "Add Assembly, Post-Build Hardware, and Post-Build Software section components"
```

---

### Task 11: Frontend — Customer Acceptance, Acknowledgement sections, and `index.vue` wiring

**Files:**
- Create: `resources/js/components/onsite_handover/CustomerAcceptanceSection.vue`
- Create: `resources/js/components/onsite_handover/AcknowledgementSection.vue`
- Create: `resources/js/components/onsite_handover/index.vue`

**Interfaces:**
- Consumes: `GET {apiBase}` (Task 2's `show()`) and all 11 section components (Tasks 8-11).
- Produces: the routable page `onsitehandover` (registered in Task 6) becomes functional.

- [ ] **Step 1: Write `CustomerAcceptanceSection.vue`**

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
  'physical_condition_accepted', 'system_boot_verified', 'display_verified', 'peripherals_verified',
  'accessories_received', 'documentation_received', 'customer_demonstration_completed',
  'customer_acceptance_notes',
];

const BOOLEAN_KEYS = [
  'physical_condition_accepted', 'system_boot_verified', 'display_verified', 'peripherals_verified',
  'accessories_received', 'documentation_received', 'customer_demonstration_completed',
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
        { key: 'peripherals_verified', label: 'Peripherals Verified' },
        { key: 'accessories_received', label: 'Accessories Received' },
        { key: 'documentation_received', label: 'Documentation Received' },
        { key: 'customer_demonstration_completed', label: 'Customer Demonstration Completed' },
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

- [ ] **Step 2: Write `AcknowledgementSection.vue`**

```vue
<template>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Acknowledgement</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h6 class="text-muted">Customer</h6>
          <div class="form-group">
            <label class="form-label">Customer Name</label>
            <input type="text" class="form-control" v-model="form.customer_ack_name">
          </div>
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="ack-customer" v-model="form.customer_acknowledged">
            <label class="custom-control-label" for="ack-customer">I acknowledge the handover as described above</label>
          </div>
        </div>
        <div class="col-md-6">
          <h6 class="text-muted">Technician</h6>
          <div class="form-group">
            <label class="form-label">Technician Name</label>
            <input type="text" class="form-control" v-model="form.technician_ack_name">
          </div>
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="ack-technician" v-model="form.technician_acknowledged">
            <label class="custom-control-label" for="ack-technician">I confirm this handover was completed as described above</label>
          </div>
        </div>
      </div>

      <div v-if="acknowledgedAt" class="text-muted small mt-2">
        Acknowledged at {{ acknowledgedAt }}
      </div>

      <div v-if="errors.length" class="alert alert-danger">
        <ul class="mb-0 pl-3"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
      </div>
      <div class="text-right">
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-2"></span>
          <i v-else class="fas fa-save mr-2"></i>
          Save Acknowledgement
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

const FIELD_KEYS = ['customer_ack_name', 'customer_acknowledged', 'technician_ack_name', 'technician_acknowledged'];
const BOOLEAN_KEYS = ['customer_acknowledged', 'technician_acknowledged'];
const STRING_KEYS = ['customer_ack_name', 'technician_ack_name'];

export default {
  props: {
    apiBase: { type: String, required: true },
    initialData: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: this.buildForm(this.initialData),
      acknowledgedAt: this.initialData.acknowledged_at || null,
      saving: false,
      errors: [],
    };
  },
  watch: {
    initialData(newVal) {
      this.form = this.buildForm(newVal);
      this.acknowledgedAt = newVal.acknowledged_at || null;
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
        const res = await axios.post(`${this.apiBase}/acknowledgement`, payload);
        this.acknowledgedAt = res.data.data.acknowledged_at;
        this.$emit('saved', res.data.data);
        Swal.fire({ title: 'Saved!', text: 'Acknowledgement updated', icon: 'success', timer: 1200, showConfirmButton: false });
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const errs = error.response.data.errors;
          this.errors = Object.keys(errs).map(field => `${field}: ${errs[field].join(', ')}`);
        } else {
          this.errors = [error.response?.data?.message || 'Failed to save acknowledgement'];
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

- [ ] **Step 3: Write `index.vue`**

```vue
<template>
  <div class="container-fluid">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border"></div>
    </div>
    <template v-else>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">OnSite Handover — {{ order.order_id }}</h4>
          <small class="text-muted">Report {{ onsiteHandover.report_id }} · Round {{ onsiteHandover.round }}</small>
        </div>
      </div>

      <report-info-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onReportInfoSaved"
      />
      <customer-info-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        :customer-name="derived.customer_name"
        :customer-id="derived.customer_id"
        :contact-number="derived.contact_number"
        :email-address="derived.email_address"
        @saved="onCustomerInfoSaved"
      />
      <build-info-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
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
        :initial-data="onsiteHandover"
        :studio-inspection-report-completed="derived.studio_inspection_report_completed"
        :studio-inspection-report-id="derived.studio_inspection_report_id"
        :performance-testing-report-completed="derived.performance_testing_report_completed"
        :performance-testing-report-id="derived.performance_testing_report_id"
        @saved="onStudioDocsSaved"
      />
      <arrival-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onArrivalSaved"
      />
      <transportation-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onTransportationSaved"
      />
      <assembly-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onAssemblySaved"
      />
      <post-build-hardware-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onPostBuildHardwareSaved"
      />
      <post-build-software-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onPostBuildSoftwareSaved"
      />
      <customer-acceptance-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onCustomerAcceptanceSaved"
      />
      <acknowledgement-section
        :api-base="apiBase"
        :initial-data="onsiteHandover"
        @saved="onAcknowledgementSaved"
      />
    </template>
  </div>
</template>

<script>
import axios from 'axios';
import ReportInfoSection from './ReportInfoSection.vue';
import CustomerInfoSection from './CustomerInfoSection.vue';
import BuildInfoSection from './BuildInfoSection.vue';
import StudioDocsSection from './StudioDocsSection.vue';
import ArrivalSection from './ArrivalSection.vue';
import TransportationSection from './TransportationSection.vue';
import AssemblySection from './AssemblySection.vue';
import PostBuildHardwareSection from './PostBuildHardwareSection.vue';
import PostBuildSoftwareSection from './PostBuildSoftwareSection.vue';
import CustomerAcceptanceSection from './CustomerAcceptanceSection.vue';
import AcknowledgementSection from './AcknowledgementSection.vue';

export default {
  components: {
    ReportInfoSection,
    CustomerInfoSection,
    BuildInfoSection,
    StudioDocsSection,
    ArrivalSection,
    TransportationSection,
    AssemblySection,
    PostBuildHardwareSection,
    PostBuildSoftwareSection,
    CustomerAcceptanceSection,
    AcknowledgementSection,
  },
  data() {
    return {
      order: {},
      onsiteHandover: null,
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
      return `/api/order/${this.orderId}/onsite-handover/${this.round}`;
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
          this.onsiteHandover = data.onsite_handover;
          this.derived = {
            customer_name: data.order.customer ? data.order.customer.full_name : null,
            customer_id: data.order.customer ? data.order.customer.customer_id : null,
            contact_number: data.order.customer ? data.order.customer.phone : null,
            email_address: data.order.customer ? data.order.customer.email : null,
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
      this.onsiteHandover = data;
    },
    onCustomerInfoSaved(data) {
      this.onsiteHandover = data;
    },
    onBuildInfoSaved(data) {
      this.onsiteHandover = data;
    },
    onStudioDocsSaved(data) {
      this.onsiteHandover = data;
    },
    onArrivalSaved(data) {
      this.onsiteHandover = data;
    },
    onTransportationSaved(data) {
      this.onsiteHandover = data;
    },
    onAssemblySaved(data) {
      this.onsiteHandover = data;
    },
    onPostBuildHardwareSaved(data) {
      this.onsiteHandover = data;
    },
    onPostBuildSoftwareSaved(data) {
      this.onsiteHandover = data;
    },
    onCustomerAcceptanceSaved(data) {
      this.onsiteHandover = data;
    },
    onAcknowledgementSaved(data) {
      this.onsiteHandover = data;
    },
  },
};
</script>
```

Every section's `@saved` handler does a full `this.onsiteHandover = data` reassignment rather than `Object.assign` — this is safe here (unlike Performance Testing's `saveForm()` bug) because every section endpoint returns `$handover->fresh()`, the complete current row, not a partial child-table response. There is no child-relation data that could be silently dropped by a full reassignment, since this feature has no child tables at all (see the spec's "Known pre-existing bug" section for why this is structurally immune to Performance Testing's `saveForm()` bug class).

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/onsite_handover/CustomerAcceptanceSection.vue resources/js/components/onsite_handover/AcknowledgementSection.vue resources/js/components/onsite_handover/index.vue
git commit -m "Add Customer Acceptance, Acknowledgement sections, and wire index.vue"
```

---

### Task 12: Quick-launch button + vault documentation

**Files:**
- Modify: `resources/js/components/order/allorder.vue`
- Modify: `docs/QuiviTech/QuiviCraft.md`
- Modify: `docs/QuiviTech/Domain-Models.md`
- Modify: `docs/QuiviTech/API-Routes.md`
- Modify: `docs/QuiviTech/Frontend-Components.md`

**Interfaces:**
- Consumes: the `onsitehandover` route name (Task 6).
- None produced — this is the final task, purely wiring the quick-launch button and updating documentation to reflect Tasks 1-11.

- [ ] **Step 1: Add the quick-launch button**

In `resources/js/components/order/allorder.vue`, find:

```html
                                                        <router-link
                                                            :to="{name:'performancetest', params:{id:order.id, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="Performance Testing"
                                                        >
                                                            <i class="fas fa-tachometer-alt"></i>
                                                        </router-link>
```

Replace with:

```html
                                                        <router-link
                                                            :to="{name:'performancetest', params:{id:order.id, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="Performance Testing"
                                                        >
                                                            <i class="fas fa-tachometer-alt"></i>
                                                        </router-link>
                                                        <router-link
                                                            :to="{name:'onsitehandover', params:{id:order.id, round:1}}"
                                                            class="btn btn-sm btn-dark ml-1"
                                                            title="OnSite Handover"
                                                        >
                                                            <i class="fas fa-truck-loading"></i>
                                                        </router-link>
```

- [ ] **Step 2: Update `docs/QuiviTech/QuiviCraft.md`**

Find the line right after Performance Testing's Phase 4 bullet (the last line of section "## 5. Performance Testing..."). Add a new section immediately after it:

```markdown

## 6. OnSite Handover (QuiviCraft)

A third, separate QC report type — created 2026-07-27, ships after all 4 Performance Testing phases per the established build order (see [[Work-In-Progress]]). Covers the on-site visit itself: arrival, transport-damage check, on-site assembly, post-build verification, customer acceptance, and acknowledgement.

- **`OnsiteHandover`** — one per `(order_id, round)`, single wide table (no child tables, unlike Craft Inspection/Performance Testing — this report has no repeated/swappable sub-forms, just a linear sequence of sections). Has its own `report_id` business code (`OSH-QVCT-XXXX`, `Model::count()+1` zero-padded convention) and its own 4-value `status` (`in_progress`/`completed`/`deferred`/`cancelled`, richer than Craft Inspection/Performance Testing's draft/completed since on-site visits can be deferred or cancelled). No separate `complete()` action — `status` is just a normal field.
- 11 sections, each with its own save endpoint writing a disjoint column subset of the same row (Report Information, Customer Information, Build Information, Studio Documentation Verification, On-Site Arrival Verification, Transportation Inspection, QuiviCraft Assembly, Post-Build Hardware Verification, Post-Build Software Verification, Customer Acceptance, Acknowledgement) — progressive per-section saving protects a multi-hour on-site visit's data against a dropped connection.
- Several Build Information / Studio Documentation fields are **not stored columns** — `show()` looks them up live from the order's actual `CraftInspection`/`PerformanceTest`/`ServeData`/`CareData` records and returns them read-only, avoiding stale/re-typed duplicate data.
- No e-signature capture. Acknowledgement uses a typed name + an "I acknowledge" tickbox for both customer and technician, with a server-set `acknowledged_at` timestamp once both are true.
- Route/API shape is `order/{orderId}/onsite-handover/{round}` — quick-launch button on the QuiviCraft list sits next to Studio Inspection's and Performance Testing's.
```

- [ ] **Step 3: Update `docs/QuiviTech/Domain-Models.md`**

Find where Performance Testing's Phase 4 models are documented and add a new paragraph after it:

```markdown
- **OnSite Handover (QuiviCraft), added 2026-07-27** — `OnsiteHandover` (table `onsite_handovers`): `SoftDeletes`, `$guarded = ['id']`, full `$casts`, one row per `(order_id, round)`. Unlike every Craft Inspection/Performance Testing table, this is the ONLY table for the entire feature — no child tables, since there's no repeated/swappable sub-form here (Craft Inspection has per-component items, Performance Testing has per-instrument results; OnSite Handover is a linear one-pass visit report). 6 `*_photos` JSON array columns, one per section that has a "Pictures" field in the source doc. `arrival_time` is a rare example of a field the source doc mentions in two different sections (a Report Information summary line and the fuller Arrival Verification section) that's modeled as a single column with exactly one writing endpoint (`updateArrival`) — `updateReportInfo` deliberately excludes it from its own field list.
```

- [ ] **Step 4: Update `docs/QuiviTech/API-Routes.md`**

Find the Performance Testing section (`## Performance testing...`) and add a new section immediately after it:

```markdown

## OnSite Handover (QuiviCraft, added 2026-07-27)
- `prefix: order/{orderId}/onsite-handover/{round}` → `OnsiteHandoverController`: `show` (GET `/`, also creates the row + generates `report_id` on first access, and returns several derived/read-only fields looked up from the order's CraftInspection/PerformanceTest/ServeData/CareData — not stored columns), then 11 section actions each posting only their own column subset of the same row: `updateReportInfo` (`.../report-info`), `updateCustomerInfo` (`.../customer-info`), `updateBuildInfo` (`.../build-info`), `updateStudioDocs` (`.../studio-docs`), `updateArrival` (`.../arrival`), `updateTransportation` (`.../transportation`), `updateAssembly` (`.../assembly`), `updatePostBuildHardware` (`.../post-build-hardware`), `updatePostBuildSoftware` (`.../post-build-software`), `updateCustomerAcceptance` (`.../customer-acceptance`), `updateAcknowledgement` (`.../acknowledgement`). No `complete` action — `status` is a normal field on `updateReportInfo`.
```

- [ ] **Step 5: Update `docs/QuiviTech/Frontend-Components.md`**

Find the "Shared Components" section (documents `ColumnSearchPanel.vue`) and add:

```markdown
- **`PhotoUploadField.vue`** (added 2026-07-27) — uncapped, notes-free photo grid widget: props `existingPhotos`/`newPhotos` (both `Array`), emits `add-photos`/`remove-existing`/`remove-new`. Generalizes `performance_test/index.vue`'s inline `PhotoNoteField` local component (which is hardcoded to a 2-photo cap and bundles in its own notes textarea) into a standalone component with no cap and no notes field — OnSite Handover's sections already have their own separate notes textareas, so only the photo-grid part needed extracting. Used by `onsite_handover/ArrivalSection.vue`, `TransportationSection.vue` (twice, one per photo field), `AssemblySection.vue`, `PostBuildHardwareSection.vue`, `PostBuildSoftwareSection.vue`.
```

Then find where Performance Testing's section components are documented and add a paragraph describing the new `onsite_handover/` directory (mirroring how Performance Testing's own section-component paragraph is written): 11 self-contained section components under `resources/js/components/onsite_handover/`, following Performance Testing's established `apiBase`/`initialData` props + `FIELD_KEYS` + `save()`-emits-`saved` contract, mounted from `onsite_handover/index.vue`.

- [ ] **Step 6: Lint the modified Vue file**

Run a manual read-through of `allorder.vue`'s changed section confirming the new `router-link` block is well-formed and doesn't break the surrounding button group's HTML structure.

- [ ] **Step 7: Commit**

```bash
git add resources/js/components/order/allorder.vue docs/QuiviTech/QuiviCraft.md docs/QuiviTech/Domain-Models.md docs/QuiviTech/API-Routes.md docs/QuiviTech/Frontend-Components.md
git commit -m "Add OnSite Handover quick-launch button and vault documentation"
```

---

## Post-plan verification (controller, not subagents)

After all 12 tasks are complete and reviewed, the controller must:

1. Run `php artisan migrate` against the dev DB (via the project's Docker image) and confirm the `onsite_handovers` table via `DESCRIBE`.
2. curl `show()` for a test order that has both a completed `CraftInspection` and a `PerformanceTest`, confirming the derived lookup fields (`studio_inspection_report_completed`/`_id`, `performance_testing_report_completed`/`_id`, the QuiviCraft/QuiviServe/QuiviCare ID+Plan fields) resolve correctly and that `report_id` was auto-generated in the `OSH-QVCT-XXXX` format.
3. curl each of the 11 section endpoints, confirming each only writes its own columns — specifically: POST to `report-info` must NOT change `arrival_time`; POST to `arrival` must NOT change any `report-info`-owned column. This is the single most important regression check given the "one column, one writer" constraint.
4. curl `updateAcknowledgement` twice — once with only `customer_acknowledged=1`, confirming `acknowledged_at` stays `null`; then with `technician_acknowledged=1` added, confirming `acknowledged_at` gets set exactly once.
5. curl a photo upload + removal round-trip on at least one photo-bearing section (e.g. `arrival`), confirming the stored file appears under `storage/app/public/onsite-handovers/` and the response's `arrival_photos` array reflects add/remove correctly.
6. Confirm the quick-launch button routes to the correct URL and the frontend bundle (`npm run watch`, already running per this project's established dev workflow) compiles cleanly with no Vue/webpack errors referencing any new file.
7. Clean up all test-order rows created during verification via `forceDelete()` in tinker, scoped to the specific test order's `onsite_handovers` row (single table, no children to clean up — simpler than every prior QC report feature's cleanup step).
