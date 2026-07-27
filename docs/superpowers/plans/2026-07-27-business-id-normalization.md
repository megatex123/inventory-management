# System-Wide Business-ID Normalization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace ~25 independently-written inline business-ID generators with one shared `App\Support\BusinessId::next()` helper for 7 named entities (Customer, Meeting, QuiviCraft, QuiviServe, QuiviCare, Supplier, QuiviCare Inventory) plus one bundled correctness fix (InvThread's prefix collision), backfill existing rows to the new format, and ship a dry-runnable artisan command to perform that backfill safely per-environment.

**Architecture:** `App\Support\BusinessId` is a stateless static helper generalizing `ProductWarrantyController::generateSerialNo()`'s existing logic (already the target scheme, per its own in-code comment). Every in-scope controller swaps its inline `str_pad($nextId, N, ...)` block for a call to this helper. A new nullable column `care_data.care_data_id` is added via migration for QuiviCare's new top-level ID. A new artisan command (`business-id:backfill`) renumbers existing rows independently of the helper (deterministic `id ASC` full-table renumbering, not "find max and add 1").

**Tech Stack:** Laravel 7 (PHP), no automated test suite — verification is manual dry-run output review + live-DB spot checks, matching this codebase's established discipline.

## Global Constraints

- 6-digit padding (`str_pad(..., 6, '0', STR_PAD_LEFT)`) applies **only** to: `customers.customer_id`, `meetings.meeting_id`, `order.order_id`, `serve_data.serve_id`, the new `care_data.care_data_id`, `suppliers.supplier_id`. Everything else keeps 4-digit padding.
- **Out of scope, must not be touched by any task in this plan:** `order.invoice_id`, `serve_data.qvse_cid`, `care_warranties.*` (including its `i_qvca_id` bug — do not fix it here), Master SKU, PC Parts codes, the Stock subsystem, "Built Draft Proposal," QuiviCare Claim, QuiviCare Substitute/loaner tracking, and every inline generator not named in this plan's 10 tasks (`onsite_handovers`, `onsite_handovers_studio`, `thread_orders`, `plus_orders`, `merch_orders`, `plus_services`, `merch_items`, `inv_merch`, `inv_excl_merch`, `inv_excl_serve`, `inv_move`, `serve_bek`, `serve_mps`, `serve_pce`'s own tier IDs, `ServeData::getNextServeId()` dead code) — they keep their current inline logic unchanged.
- `App\Support\BusinessId::next()` has no locking/transaction wrapping and no internal retry-on-collision — this matches this app's existing, accepted risk posture for every other business-code generator (a read-then-write race under concurrent creation is a known, accepted, pre-existing characteristic of this codebase, not a new gap this plan introduces).
- Where a call site being migrated (`OrderController::updatecare()`, `CareDataController::store()`) currently has its own extra `while (...->exists())` retry loop, that loop is **removed**, not preserved — `BusinessId::next()` is called once, matching every other now-migrated entity's risk posture. (Naively re-wrapping a bare `BusinessId::next()` call in a retry loop that doesn't insert anything between attempts would return the identical value forever — an infinite loop, not real safety — so faithfully preserving the old retry semantics would require re-deriving the helper's internal increment logic outside the helper, which the "generalize it as-is, no retry added" design decision doesn't call for.)
- No automated tests exist. Verification for every task is: read the diff, confirm no syntax errors via `php -l`, and (for Tasks 9 and 11) a live check against the local dev DB.

---

### Task 1: Create the `BusinessId` helper

**Files:**
- Create: `app/Support/BusinessId.php`

**Interfaces:**
- Produces: `App\Support\BusinessId::next(string $table, string $column, string $prefix, int $pad = 6): string` — every other task in this plan calls this exact signature.

- [ ] **Step 1: Create the helper class**

Create `app/Support/BusinessId.php`:
```php
<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class BusinessId
{
    /**
     * Generalizes ProductWarrantyController::generateSerialNo()'s existing
     * logic (already the "Normalize ID" target scheme) into one shared helper.
     * No locking/retry — matches this app's existing accepted concurrency
     * risk posture for business-code generation.
     */
    public static function next(string $table, string $column, string $prefix, int $pad = 6): string
    {
        $lastValue = DB::table($table)
            ->where($column, 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value($column);

        $nextNumber = 1;
        if ($lastValue) {
            preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastValue, $matches);
            $nextNumber = (isset($matches[1]) ? (int) $matches[1] : 0) + 1;
        }

        return $prefix . str_pad((string) $nextNumber, $pad, '0', STR_PAD_LEFT);
    }
}
```

- [ ] **Step 2: Verify and commit**

Run: `php -l app/Support/BusinessId.php`
Expected: `No syntax errors detected`

```bash
git add app/Support/BusinessId.php
git commit -m "Add shared BusinessId::next() helper for business-code generation"
```

---

### Task 2: Refactor `ProductWarrantyController::generateSerialNo()` to use the helper

**Files:**
- Modify: `app/Http/Controllers/ProductWarrantyController.php`

**Interfaces:**
- Consumes: `App\Support\BusinessId::next(string $table, string $column, string $prefix, int $pad = 6): string` (Task 1).

- [ ] **Step 1: Add the import**

Find:
```php
namespace App\Http\Controllers;

use App\Models\ProductWarranty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
```

Replace with:
```php
namespace App\Http\Controllers;

use App\Models\ProductWarranty;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
```

- [ ] **Step 2: Replace the inline logic with the helper call**

Find:
```php
    public function generateSerialNo()
    {
        try {
            // QV-WRTY-XXXXXX per the "Normalize ID" spec (generic warranty entity) —
            // continuously incrementing, no date component (unlike the old PW-YYYYMM#### scheme).
            $prefix = 'QV-WRTY-';

            $lastRecord = ProductWarranty::where('serial_no', 'like', $prefix . '%')
                ->orderBy('id', 'desc')
                ->first();

            if ($lastRecord) {
                preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastRecord->serial_no, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $nextNumber = '000001';
            }

            $serialNo = $prefix . $nextNumber;

            return response()->json([
                'success' => true,
                'data' => [
                    'serial_no' => $serialNo,
                    'serialNo' => $serialNo,
                    'prefix' => $prefix,
                    'sequence' => $nextNumber
                ],
                'message' => 'Serial number generated successfully'
            ]);

        } catch (\Exception $e) {
```

Replace with:
```php
    public function generateSerialNo()
    {
        try {
            // QV-WRTY-XXXXXX per the "Normalize ID" spec (generic warranty entity) —
            // continuously incrementing, no date component (unlike the old PW-YYYYMM#### scheme).
            $prefix = 'QV-WRTY-';

            $serialNo = BusinessId::next('product_warranties', 'serial_no', $prefix, 6);
            $nextNumber = substr($serialNo, strlen($prefix));

            return response()->json([
                'success' => true,
                'data' => [
                    'serial_no' => $serialNo,
                    'serialNo' => $serialNo,
                    'prefix' => $prefix,
                    'sequence' => $nextNumber
                ],
                'message' => 'Serial number generated successfully'
            ]);

        } catch (\Exception $e) {
```

The response shape (`serial_no`/`serialNo`/`prefix`/`sequence` keys) is preserved exactly — only the generation logic is deduplicated into the shared helper.

- [ ] **Step 3: Verify and commit**

Run: `php -l app/Http/Controllers/ProductWarrantyController.php`
Expected: `No syntax errors detected`

```bash
git add app/Http/Controllers/ProductWarrantyController.php
git commit -m "Refactor ProductWarrantyController to use the shared BusinessId helper"
```

---

### Task 3: Customer — `customers.customer_id` → 6-digit

**Files:**
- Modify: `app/Http/Controllers/CustomersController.php`

**Interfaces:**
- Consumes: `App\Support\BusinessId::next()` (Task 1).

- [ ] **Step 1: Add the import**

Find:
```php
namespace App\Http\Controllers;

use App\Models\Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Image;
use Carbon\Carbon;
```

Replace with:
```php
namespace App\Http\Controllers;

use App\Models\Customers;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Image;
use Carbon\Carbon;
```

- [ ] **Step 2: Replace the inline logic**

Find:
```php
            $totalCustomers = Customers::where('deleted_at', null)->count();
            $nextId = $totalCustomers + 1;
            $serveNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
            $customerId = "QVCST-{$serveNumber}";
```

Replace with:
```php
            $customerId = BusinessId::next('customers', 'customer_id', 'QV-CUST-', 6);
```

Note the prefix also changes from `QVCST-` to `QV-CUST-` (the hyphenated form) per the design spec's scope table — this is a deliberate format change, not just a padding change.

- [ ] **Step 3: Verify and commit**

Run: `php -l app/Http/Controllers/CustomersController.php`
Expected: `No syntax errors detected`

```bash
git add app/Http/Controllers/CustomersController.php
git commit -m "Migrate customer_id generation to BusinessId::next(), 6-digit QV-CUST- prefix"
```

---

### Task 4: Meeting — `meetings.meeting_id` → 6-digit

**Files:**
- Modify: `app/Http/Controllers/MeetingController.php`

**Interfaces:**
- Consumes: `App\Support\BusinessId::next()` (Task 1).

- [ ] **Step 1: Add the import**

Find:
```php
namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
```

Replace with:
```php
namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Customers;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
```

- [ ] **Step 2: Replace the inline logic**

Find:
```php
        try {
            $nextId = DB::table('meetings')->max('id') + 1;
            $meetingNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
            $meetingId = 'QV-MEET-' . $meetingNumber;
```

Replace with:
```php
        try {
            $meetingId = BusinessId::next('meetings', 'meeting_id', 'QV-MEET-', 6);
```

- [ ] **Step 3: Verify and commit**

Run: `php -l app/Http/Controllers/MeetingController.php`
Expected: `No syntax errors detected`

```bash
git add app/Http/Controllers/MeetingController.php
git commit -m "Migrate meeting_id generation to BusinessId::next(), 6-digit"
```

---

### Task 5: QuiviCraft — `order.order_id` → 6-digit

**Files:**
- Modify: `app/Http/Controllers/PosController.php`

**Interfaces:**
- Consumes: `App\Support\BusinessId::next()` (Task 1).

- [ ] **Step 1: Add the import**

Find:
```php
namespace App\Http\Controllers;

use App\products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
```

Replace with:
```php
namespace App\Http\Controllers;

use App\products;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
```

- [ ] **Step 2: Replace the inline logic**

Find:
```php
    $nextId = DB::table('order')->max('id') + 1;
    $orderNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
    $orderId = 'QV-ORDR-' . $orderNumber;
```

Replace with:
```php
    $orderId = BusinessId::next('order', 'order_id', 'QV-ORDR-', 6);
```

`order.invoice_id` (a separate field, set later at approval elsewhere in this codebase) is out of scope — do not touch it.

- [ ] **Step 3: Verify and commit**

Run: `php -l app/Http/Controllers/PosController.php`
Expected: `No syntax errors detected`

```bash
git add app/Http/Controllers/PosController.php
git commit -m "Migrate order_id generation to BusinessId::next(), 6-digit"
```

---

### Task 6: Supplier — `suppliers.supplier_id` → 6-digit

**Files:**
- Modify: `app/Http/Controllers/SuppliersController.php`

**Interfaces:**
- Consumes: `App\Support\BusinessId::next()` (Task 1).

- [ ] **Step 1: Add the import**

Find:
```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Image;
```

Replace with:
```php
namespace App\Http\Controllers;

use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Image;
```

- [ ] **Step 2: Replace the inline logic**

Find:
```php
        try {

            $nextId = DB::table('suppliers')->max('id') + 1;
            $meetingNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
            $supplierId = 'QV-SUPP-' . $meetingNumber;
```

Replace with:
```php
        try {

            $supplierId = BusinessId::next('suppliers', 'supplier_id', 'QV-SUPP-', 6);
```

(The original variable name `$meetingNumber` for a supplier ID looks like copy-paste from `MeetingController` — it disappears naturally as part of this refactor, not a separate fix.)

- [ ] **Step 3: Verify and commit**

Run: `php -l app/Http/Controllers/SuppliersController.php`
Expected: `No syntax errors detected`

```bash
git add app/Http/Controllers/SuppliersController.php
git commit -m "Migrate supplier_id generation to BusinessId::next(), 6-digit"
```

---

### Task 7: QuiviServe — `serve_data.serve_id` → 6-digit

**Files:**
- Modify: `app/Http/Controllers/OrderController.php`

**Interfaces:**
- Consumes: `App\Support\BusinessId::next()` (Task 1).

**Do not touch** the `$serve_pce_id` generation a few lines above this block (a different, out-of-scope entity), nor `serve_data.qvse_cid` generation later in this same file (out of scope, ServeData's own tier sub-code).

- [ ] **Step 1: Add the import**

Find:
```php
namespace App\Http\Controllers;

use App\Models\ServeBek;
use App\Models\ServeMps;
use App\Models\ServePce;
use App\Models\CareData;
use App\Models\ServeData;
use App\Models\Care;
use App\Models\Serves;
use App\Models\Categories;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Customers;
```

Replace with:
```php
namespace App\Http\Controllers;

use App\Models\ServeBek;
use App\Models\ServeMps;
use App\Models\ServePce;
use App\Models\CareData;
use App\Models\ServeData;
use App\Models\Care;
use App\Models\Serves;
use App\Models\Categories;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Customers;
use App\Support\BusinessId;
```

- [ ] **Step 2: Replace the inline logic**

Find:
```php
                // Create new serve data if it doesn't exist
                $totalServes = ServeData::count();
                $nextId = $totalServes + 1;
                $serveNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
                $serveId = "QV-SRV-{$serveNumber}";
```

Replace with:
```php
                // Create new serve data if it doesn't exist
                $serveId = BusinessId::next('serve_data', 'serve_id', 'QV-SRV-', 6);
```

- [ ] **Step 3: Verify and commit**

Run: `php -l app/Http/Controllers/OrderController.php`
Expected: `No syntax errors detected`

```bash
git add app/Http/Controllers/OrderController.php
git commit -m "Migrate serve_id generation to BusinessId::next(), 6-digit"
```

---

### Task 8: QuiviCare — new top-level `care_data.care_data_id` + canonicalize `care_id`

**Files:**
- Create: `database/migrations/2026_07_27_300000_add_care_data_id_to_care_data_table.php`
- Modify: `app/Models/CareData.php`
- Modify: `app/Http/Controllers/OrderController.php` (this task's changes are independent of Task 7's changes to the same file — apply after Task 7 if working sequentially, or note both touch this file)
- Modify: `app/Http/Controllers/CareDataController.php`

**Interfaces:**
- Consumes: `App\Support\BusinessId::next()` (Task 1).
- Produces: `care_data.care_data_id` column, populated on every new `CareData::create()` call from this point forward. Task 12's backfill command depends on this column existing.

**This task also canonicalizes `care_id`'s format**, fixing a pre-existing inconsistency: `OrderController::updatecare()` (auto-create on order approval) currently produces `{full tier code}-{seq}` e.g. `COR3-1402-0001`; `CareDataController::store()` (manual create) currently produces `{3-char code}-{today's month/year}-{seq}` e.g. `COR-2712-0001`. Both become `{full tier code}-{seq}`, matching `updatecare()`'s existing format (chosen because it doesn't drift with creation date and already matches the tier lookup table's fixed code).

- [ ] **Step 1: Migration — add the new column**

Create `database/migrations/2026_07_27_300000_add_care_data_id_to_care_data_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCareDataIdToCareDataTable extends Migration
{
    public function up()
    {
        Schema::table('care_data', function (Blueprint $table) {
            $table->string('care_data_id')->nullable()->after('care_id');
        });
    }

    public function down()
    {
        Schema::table('care_data', function (Blueprint $table) {
            $table->dropColumn('care_data_id');
        });
    }
}
```

Run: `docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan migrate`
Expected: the new migration runs successfully, no errors.

- [ ] **Step 2: Add `care_data_id` to the model's `$fillable`**

Find:
```php
    protected $fillable = [
        'care_id',
        'customer_id',
        'order_id',
        'lkp_care_id',
        'total_part',
        'price',
        'update_membership',
    ];
```

Replace with:
```php
    protected $fillable = [
        'care_id',
        'care_data_id',
        'customer_id',
        'order_id',
        'lkp_care_id',
        'total_part',
        'price',
        'update_membership',
    ];
```

(This is in `app/Models/CareData.php`.)

- [ ] **Step 3: `OrderController::updatecare()` — canonicalize `care_id` and generate `care_data_id`**

Find:
```php
                } else {
                    $careType = Care::find($lkp_care_id);
                    $careTypeCode = $careType ? strtoupper(substr($careType->code, 0)) : 'QV-CARE';
                    $lastCare = CareData::where('lkp_care_id', $lkp_care_id)
                        ->orderBy('id', 'desc')
                        ->first();

                    $sequence = $lastCare ?
                        intval(substr($lastCare->care_id, -4)) + 1 : 1;
                    $sequenceNumber = str_pad($sequence, 4, '0', STR_PAD_LEFT);
                    $careId = "{$careTypeCode}-{$sequenceNumber}";

                    // Ensure uniqueness
                    while (CareData::where('care_id', $careId)->exists()) {
                        $sequence++;
                        $sequenceNumber = str_pad($sequence, 4, '0', STR_PAD_LEFT);
                        $careId = "{$careTypeCode}-{$sequenceNumber}";
                    }

                    $careData = CareData::create([
                        'care_id' => $careId,
                        'customer_id' => $order->customer_id,
                        'order_id' => $order->id,
                        'lkp_care_id' => $lkp_care_id,
                        'total_part' => (int)$order->total,
                        'price' => $care_charge,
                    ]);

                    $message = 'Care data created successfully';
                }
```

Replace with:
```php
                } else {
                    $careType = Care::find($lkp_care_id);
                    $careTypeCode = $careType ? strtoupper(substr($careType->code, 0)) : 'QV-CARE';
                    $careId = BusinessId::next('care_data', 'care_id', "{$careTypeCode}-", 4);
                    $careDataId = BusinessId::next('care_data', 'care_data_id', 'QV-CARE-', 6);

                    $careData = CareData::create([
                        'care_id' => $careId,
                        'care_data_id' => $careDataId,
                        'customer_id' => $order->customer_id,
                        'order_id' => $order->id,
                        'lkp_care_id' => $lkp_care_id,
                        'total_part' => (int)$order->total,
                        'price' => $care_charge,
                    ]);

                    $message = 'Care data created successfully';
                }
```

**No import step needed for `OrderController.php` here** — Task 7 (which must run before this task, per this plan's task order) already added `use App\Support\BusinessId;` to this file's import block.

- [ ] **Step 4: `CareDataController::store()` — canonicalize `care_id` and generate `care_data_id`**

Find:
```php
            // Generate unique care_id based on QVCA pattern
            $careType = Care::find($request->lkp_care_id);
            $careTypeCode = $careType ? strtoupper(substr($careType->code, 0, 3)) : 'VIS';

            // Find next sequence for this care type
            $lastCare = CareData::where('lkp_care_id', $request->lkp_care_id)
                ->orderBy('id', 'desc')
                ->first();

            $sequence = $lastCare ?
                intval(substr($lastCare->care_id, -4)) + 1 : 1;
            $sequenceNumber = str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // Generate care ID: QVCA-2712-0001 pattern
            $monthYear = date('my'); // Format: 2712 for December 2027
            $careId = "{$careTypeCode}-{$monthYear}-{$sequenceNumber}";

            // Ensure uniqueness
            while (CareData::where('care_id', $careId)->exists()) {
                $sequence++;
                $sequenceNumber = str_pad($sequence, 4, '0', STR_PAD_LEFT);
                $careId = "{$careTypeCode}-{$monthYear}-{$sequenceNumber}";
            }

            $careData = CareData::create([
                'care_id' => $careId,
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'lkp_care_id' => $request->lkp_care_id,
                'total_part' => $request->total_part ?? 0,
                'price' => $request->price,
                'status' => $request->status ?? 'pending',
                'appointment_date' => $request->appointment_date,
                'appointment_time' => $request->appointment_time,
                'notes' => $request->notes,
                'service_duration' => $request->service_duration,
```

Replace with:
```php
            // Generate care_id using the same canonical format as OrderController's
            // auto-create path: full tier code + sequence, no date component (fixes
            // the pre-existing inconsistency between the two entry points).
            $careType = Care::find($request->lkp_care_id);
            $careTypeCode = $careType ? strtoupper(substr($careType->code, 0)) : 'QV-CARE';
            $careId = BusinessId::next('care_data', 'care_id', "{$careTypeCode}-", 4);
            $careDataId = BusinessId::next('care_data', 'care_data_id', 'QV-CARE-', 6);

            $careData = CareData::create([
                'care_id' => $careId,
                'care_data_id' => $careDataId,
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'lkp_care_id' => $request->lkp_care_id,
                'total_part' => $request->total_part ?? 0,
                'price' => $request->price,
                'status' => $request->status ?? 'pending',
                'appointment_date' => $request->appointment_date,
                'appointment_time' => $request->appointment_time,
                'notes' => $request->notes,
                'service_duration' => $request->service_duration,
```

- [ ] **Step 5: `CareDataController.php` — add the import**

Find:
```php
namespace App\Http\Controllers;

use App\Models\CareData;
use App\Models\Care;
use App\Models\Customers;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
```

Replace with:
```php
namespace App\Http\Controllers;

use App\Models\CareData;
use App\Models\Care;
use App\Models\Customers;
use App\Models\Order;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
```

- [ ] **Step 6: Verify and commit**

Run: `php -l app/Http/Controllers/OrderController.php && php -l app/Http/Controllers/CareDataController.php && php -l app/Models/CareData.php`
Expected: `No syntax errors detected` for all three.

```bash
git add database/migrations/2026_07_27_300000_add_care_data_id_to_care_data_table.php app/Models/CareData.php app/Http/Controllers/OrderController.php app/Http/Controllers/CareDataController.php
git commit -m "Add care_data.care_data_id and canonicalize care_id generation format"
```

---

### Task 9: QuiviCare Inventory — `inv_care.inv_care` → per-category prefix

**Files:**
- Modify: `app/Http/Controllers/InvCareController.php`

**Interfaces:**
- Consumes: `App\Support\BusinessId::next()` (Task 1), `App\Models\Categories` (already imported in this file).

`inv_care`'s new prefix is `IC-{category.name}-` where `category.name` comes from the existing `categories` lookup table (values already short-code-shaped: `CPU`, `GPU`, `PER-MON`, etc.) — the LIKE-based lookup inside `BusinessId::next()` naturally gives each category its own independent sequence, since the prefix itself includes the category segment.

- [ ] **Step 1: Add the import**

Find:
```php
namespace App\Http\Controllers;

use App\Models\InvCare;
use App\Models\MasterSku;
use App\Models\Categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
```

Replace with:
```php
namespace App\Http\Controllers;

use App\Models\InvCare;
use App\Models\MasterSku;
use App\Models\Categories;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
```

- [ ] **Step 2: Replace the inline logic**

Find:
```php
        DB::beginTransaction();
        try {
            $nextId = InvCare::count() + 1;
            $invCareCode = 'IC-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
```

Replace with:
```php
        DB::beginTransaction();
        try {
            $categoryName = Categories::find($request->category)->name ?? 'MISC';
            $invCareCode = BusinessId::next('inv_care', 'inv_care', "IC-{$categoryName}-", 4);
```

If `$request->category` is empty or doesn't resolve to a category, the prefix falls back to `IC-MISC-` (a distinct, self-consistent grouping) rather than erroring — matches the existing validation rule (`'category' => 'nullable|exists:categories,id'`), which already allows an empty category.

- [ ] **Step 3: Verify and commit**

Run: `php -l app/Http/Controllers/InvCareController.php`
Expected: `No syntax errors detected`

```bash
git add app/Http/Controllers/InvCareController.php
git commit -m "Migrate inv_care generation to per-category-prefix BusinessId::next()"
```

---

### Task 10: InvThread — fix the prefix collision with InvMerch

**Files:**
- Modify: `app/Http/Controllers/InvThreadController.php`

**Interfaces:**
- Consumes: `App\Support\BusinessId::next()` (Task 1).

This is a bundled correctness fix, not a full renumbering — `inv_thread` currently generates `I-QVMR-XXXX`, identical to InvMerch's own prefix (a copy-paste bug). This task gives InvThread its own distinct prefix, `I-QVTD-`, staying 4-digit. InvMerch itself (`I-QVMR-XXXX`) is untouched — it's not part of this plan.

- [ ] **Step 1: Add the import**

Find:
```php
namespace App\Http\Controllers;

use App\Models\InvThread;
use App\Models\MasterSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
```

Replace with:
```php
namespace App\Http\Controllers;

use App\Models\InvThread;
use App\Models\MasterSku;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
```

- [ ] **Step 2: Replace the inline logic**

Find:
```php
        DB::beginTransaction();
        try {
            $nextId = InvThread::count() + 1;
            $code = 'I-QVMR-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
```

Replace with:
```php
        DB::beginTransaction();
        try {
            $code = BusinessId::next('inv_thread', 'inv_thread_id', 'I-QVTD-', 4);
```

- [ ] **Step 3: Verify and commit**

Run: `php -l app/Http/Controllers/InvThreadController.php`
Expected: `No syntax errors detected`

```bash
git add app/Http/Controllers/InvThreadController.php
git commit -m "Fix InvThread prefix collision with InvMerch (I-QVMR- -> I-QVTD-)"
```

---

### Task 11: Backfill artisan command

**Files:**
- Create: `app/Console/Commands/BusinessIdBackfill.php`

**Interfaces:**
- None — this command operates directly on the database via `DB::table()`, independent of `BusinessId::next()` (it needs deterministic full-table `id ASC` renumbering starting at `000001`, not "find the current max and add 1" incremental logic, so it does not call the Task 1 helper).
- Depends on Task 8's migration having already run (`care_data.care_data_id` must exist as a column before this command can backfill it).

Laravel auto-discovers commands placed in `app/Console/Commands/` (confirmed via `app/Console/Kernel.php`'s `$this->load(__DIR__.'/Commands')` — no separate registration step needed).

- [ ] **Step 1: Create the command**

Create `app/Console/Commands/BusinessIdBackfill.php`:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BusinessIdBackfill extends Command
{
    protected $signature = 'business-id:backfill {entity?} {--dry-run}';

    protected $description = 'Renumber existing business/display IDs to the normalized format (full backfill, not just new records)';

    private const SIMPLE_ENTITIES = [
        'customer' => ['table' => 'customers', 'column' => 'customer_id', 'prefix' => 'QV-CUST-', 'pad' => 6],
        'meeting' => ['table' => 'meetings', 'column' => 'meeting_id', 'prefix' => 'QV-MEET-', 'pad' => 6],
        'order' => ['table' => 'order', 'column' => 'order_id', 'prefix' => 'QV-ORDR-', 'pad' => 6],
        'serve' => ['table' => 'serve_data', 'column' => 'serve_id', 'prefix' => 'QV-SRV-', 'pad' => 6],
        'supplier' => ['table' => 'suppliers', 'column' => 'supplier_id', 'prefix' => 'QV-SUPP-', 'pad' => 6],
    ];

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $entity = $this->argument('entity');

        $validEntities = array_merge(array_keys(self::SIMPLE_ENTITIES), ['care', 'inv-care']);
        if ($entity && !in_array($entity, $validEntities, true)) {
            $this->error("Unknown entity '{$entity}'. Valid: " . implode(', ', $validEntities));
            return 1;
        }

        if ($dryRun) {
            $this->comment('DRY RUN — no changes will be written.');
        }

        foreach (self::SIMPLE_ENTITIES as $key => $config) {
            if ($entity && $entity !== $key) {
                continue;
            }
            $this->backfillSimple($key, $config, $dryRun);
        }

        if (!$entity || $entity === 'care') {
            $this->backfillCareDataId($dryRun);
        }

        if (!$entity || $entity === 'inv-care') {
            $this->backfillInvCare($dryRun);
        }

        return 0;
    }

    private function backfillSimple(string $label, array $config, bool $dryRun)
    {
        $this->info("=== {$label} ({$config['table']}.{$config['column']}) ===");

        $rows = DB::table($config['table'])->orderBy('id')->get(['id', $config['column']]);

        $sequence = 0;
        foreach ($rows as $row) {
            $sequence++;
            $newValue = $config['prefix'] . str_pad((string) $sequence, $config['pad'], '0', STR_PAD_LEFT);
            $oldValue = $row->{$config['column']};

            if ($oldValue === $newValue) {
                continue;
            }

            $this->line("  id {$row->id}: " . ($oldValue ?? '(none)') . " -> {$newValue}");

            if (!$dryRun) {
                DB::table($config['table'])->where('id', $row->id)->update([$config['column'] => $newValue]);
            }
        }
    }

    private function backfillCareDataId(bool $dryRun)
    {
        $this->info('=== care (care_data.care_data_id) ===');

        $rows = DB::table('care_data')->orderBy('id')->get(['id', 'care_data_id']);

        $sequence = 0;
        foreach ($rows as $row) {
            $sequence++;
            $newValue = 'QV-CARE-' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $oldValue = $row->care_data_id;

            if ($oldValue === $newValue) {
                continue;
            }

            $this->line("  id {$row->id}: " . ($oldValue ?? '(none)') . " -> {$newValue}");

            if (!$dryRun) {
                DB::table('care_data')->where('id', $row->id)->update(['care_data_id' => $newValue]);
            }
        }
    }

    private function backfillInvCare(bool $dryRun)
    {
        $this->info('=== inv-care (inv_care.inv_care, per-category) ===');

        $categories = DB::table('categories')->pluck('name', 'id');
        $rows = DB::table('inv_care')->orderBy('id')->get(['id', 'inv_care', 'category']);
        $grouped = $rows->groupBy('category');

        foreach ($grouped as $categoryId => $categoryRows) {
            $categoryName = $categories[$categoryId] ?? 'MISC';
            $prefix = "IC-{$categoryName}-";

            $sequence = 0;
            foreach ($categoryRows->sortBy('id') as $row) {
                $sequence++;
                $newValue = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
                $oldValue = $row->inv_care;

                if ($oldValue === $newValue) {
                    continue;
                }

                $this->line("  id {$row->id} (category {$categoryName}): " . ($oldValue ?? '(none)') . " -> {$newValue}");

                if (!$dryRun) {
                    DB::table('inv_care')->where('id', $row->id)->update(['inv_care' => $newValue]);
                }
            }
        }
    }
}
```

- [ ] **Step 2: Verify and commit**

Run: `php -l app/Console/Commands/BusinessIdBackfill.php`
Expected: `No syntax errors detected`

```bash
git add app/Console/Commands/BusinessIdBackfill.php
git commit -m "Add business-id:backfill artisan command with dry-run support"
```

---

## Post-plan steps (controller-owned, not a task)

1. **Live verification against the local dev DB**, in this order:
   - `docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan business-id:backfill --dry-run` — review the full before/after output for all 7 entities, confirm no surprises (e.g. sequences starting where expected, `inv_care` grouped correctly per category).
   - Run the same command without `--dry-run` against local dev's small dataset (11-12 rows per table, confirmed earlier).
   - Spot-check a handful of renumbered rows directly via a DB query per entity, confirming sequence continuity (no gaps/duplicates within a prefix group).
   - Create one new record per entity through the actual app UI (or API) and confirm it gets the new format going forward, not just the backfilled rows.
2. **Vault documentation**: per `CLAUDE.md`'s rule table, this is a genuinely new cross-cutting subsystem — create a new note (e.g. `docs/QuiviTech/Business-ID-Normalization.md`) documenting the `BusinessId::next()` helper, the final prefix table, the backfill command, and which entities/generators were deliberately left untouched — link it from `General.md` and from each affected entity's own existing note (`Domain-Models.md`, etc.).
3. Dispatch the final whole-branch review (`scripts/review-package`) covering all 11 tasks' combined diff before this branch ships.
4. Use `finishing-a-development-branch`.
