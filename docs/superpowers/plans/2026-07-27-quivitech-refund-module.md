# QuiviTech Refund Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a full CRUD "Refund" module (table, model, controller, API routes, Vue list/create/edit/print pages, sidebar entry) for the Quivitech Laravel 7 + Vue 2 SPA, per the approved spec at `docs/superpowers/specs/2026-07-27-quivitech-refund-design.md`.

**Architecture:** Mirrors the existing `PlusOrder`/`MerchOrder` module pattern exactly (migration → model → controller → `routes/api.php` block → Vue CRUD pages → `routes.js` entries), with one simplification already approved: a single `refund_amount` field instead of a line-items child table, and nullable links to `order`/`plus_orders`/`merch_orders`/`thread_orders` instead of separate Craft/Serve/Care columns (those three already live on the `order` record itself).

**Tech Stack:** Laravel 7 (PHP 7.4), Eloquent, Vue 2 Options API, vue-router, axios, SweetAlert2, Bootstrap 4.

## Global Constraints

- `refund_id` is generated via the existing shared helper `App\Support\BusinessId::next('refunds', 'refund_id', 'QV-REFD-', 6)` (produces `QV-REFD-000001`) — every business-ID entity in this app uses this helper, never ad hoc generation.
- No automated test suite exists anywhere in this codebase (established project-wide) — verification at every step is manual: `php -l` for syntax, `php artisan tinker` for data/behavior spot-checks, a one-shot webpack build for frontend changes, and exercising the UI directly.
- All PHP/Composer/Artisan commands run through the project's own Docker image, never host PHP: `host-spawn docker exec quivitech-im-dev <command>` (container must already be running — `docker run -d --name quivitech-im-dev --network host -v "$(pwd):/var/www/html" quivitech-im:local` if not).
- Frontend build commands run via `nvm use 12` first (Node 12, not the system Node).
- Do **not** write migrations using `->change()`, `renameColumn()`, or `getDoctrineSchemaManager()` — `doctrine/dbal` isn't installed and can't be added to this Laravel 7 + locked `nesbot/carbon` combo. Use raw `DB::statement()` if a column ever needs altering (not needed by this plan — all new tables/columns).
- Per the approved spec's explicit out-of-scope list: no `deposits` table (deposit is a plain `deposit_amount` field), no `refund_items` child table (single `refund_amount` field), no enforcement preventing a refund from linking to a `plus_order_id` despite QuiviPlus's documented "no refunds" policy (business-process decision, not this module's job), no PDF export library (print view is `window.print()` only).
- **Do not run `php artisan db:seed --class=MenuItemsTableSeeder`.** That seeder's `run()` starts with `MenuItem::query()->delete()` and rebuilds the entire sidebar from its own hardcoded `$tree` array — which is now stale relative to the live DB (the live `menu_items` table has a "Stock" top-level group with items moved out of "Inventory" and an icon fix, shipped directly via `quivi.sql`/DB in commit `1945073`, never back-ported into the seeder file). Re-running it would silently revert that live reorg. Task 5 adds the new Refund menu entry via a standalone additive migration instead.

---

### Task 1: `refunds` table migration + `Refund` model

**Files:**
- Create: `database/migrations/2026_07_28_150000_create_refunds_table.php`
- Create: `app/Models/Refund.php`

**Interfaces:**
- Produces: `refunds` table with columns `id`, `refund_id` (string, unique), `customer_id` (unsignedBigInteger), `order_id`/`plus_order_id`/`merch_order_id`/`thread_order_id` (all nullable unsignedBigInteger), `refund_amount`/`deposit_amount` (decimal(10,2), the latter nullable), `payment_type` (nullable string), `cash_journal` (boolean, default false), `notes` (nullable text), `refunded_at` (nullable timestamp), `timestamps()`, `softDeletes()`.
- Produces: `App\Models\Refund` — `$fillable` covers every column except `id`/timestamps; relations `customer()`, `order()`, `plusOrder()`, `merchOrder()`, `threadOrder()` (all `belongsTo`). Later tasks (`RefundController`) call `Refund::create()`, `Refund::find()`, `Refund::with([...])`, and load these five relation names.

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundsTable extends Migration
{
    public function up()
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_id', 50)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('plus_order_id')->nullable();
            $table->unsignedBigInteger('merch_order_id')->nullable();
            $table->unsignedBigInteger('thread_order_id')->nullable();
            $table->decimal('refund_amount', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->string('payment_type', 50)->nullable();
            $table->boolean('cash_journal')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('order_id');
            $table->index('plus_order_id');
            $table->index('merch_order_id');
            $table->index('thread_order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('refunds');
    }
}
```

- [ ] **Step 2: Run the migration and verify the table**

```bash
host-spawn docker exec quivitech-im-dev php artisan migrate --path=database/migrations/2026_07_28_150000_create_refunds_table.php
host-spawn docker exec lokaldb mariadb -uroot -p'nopassword2026!' quivi -e "DESCRIBE refunds;"
```
Expected: migration reports "Migrated", `DESCRIBE` lists all 14 columns above plus `id`/`created_at`/`updated_at`/`deleted_at`.

- [ ] **Step 3: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model {
    use SoftDeletes;
    protected $table = 'refunds';
    protected $guarded = ['id'];

    protected $fillable = [
        'refund_id',
        'customer_id',
        'order_id',
        'plus_order_id',
        'merch_order_id',
        'thread_order_id',
        'refund_amount',
        'deposit_amount',
        'payment_type',
        'cash_journal',
        'notes',
        'refunded_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'order_id' => 'integer',
        'plus_order_id' => 'integer',
        'merch_order_id' => 'integer',
        'thread_order_id' => 'integer',
        'refund_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'cash_journal' => 'boolean',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function customer() {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function order() {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function plusOrder() {
        return $this->belongsTo(PlusOrder::class, 'plus_order_id');
    }

    public function merchOrder() {
        return $this->belongsTo(MerchOrder::class, 'merch_order_id');
    }

    public function threadOrder() {
        return $this->belongsTo(ThreadOrder::class, 'thread_order_id');
    }
}
```

- [ ] **Step 4: Verify the model in tinker**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$id = \App\Support\BusinessId::next('refunds', 'refund_id', 'QV-REFD-', 6);
echo \$id . PHP_EOL;
\$r = \App\Models\Refund::create(['refund_id' => \$id, 'customer_id' => 1, 'refund_amount' => 10.50]);
echo \$r->fresh()->refund_id . ' ' . \$r->fresh()->refund_amount . PHP_EOL;
\$r->delete();
"
```
Expected: prints `QV-REFD-000001` then `QV-REFD-000001 10.50`, no errors. (Assumes a customer with `id` 1 exists in the local dev DB — if not, substitute any real customer `id` from `Customers::first()->id`.)

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_28_150000_create_refunds_table.php app/Models/Refund.php
git commit -m "Add refunds table and Refund model"
```

---

### Task 2: `RefundController` + API routes + `API-Routes.md`

**Files:**
- Create: `app/Http/Controllers/RefundController.php`
- Modify: `routes/api.php` (append a new route block; find the end of the existing `plus-orders`/`merch-orders`/`thread-orders` blocks to place it near them)
- Modify: `docs/QuiviTech/API-Routes.md` (document the new routes)

**Interfaces:**
- Consumes: `App\Models\Refund` (Task 1) — `$fillable` fields `refund_id`, `customer_id`, `order_id`, `plus_order_id`, `merch_order_id`, `thread_order_id`, `refund_amount`, `deposit_amount`, `payment_type`, `cash_journal`, `notes`, `refunded_at`; relations `customer()`, `order()`, `plusOrder()`, `merchOrder()`, `threadOrder()`. `App\Support\BusinessId::next(string $table, string $column, string $prefix, int $pad)`.
- Produces: `GET/POST /api/refunds`, `GET /api/refunds/order-options`, `GET /api/refunds/statistics`, `GET/PUT/PATCH/DELETE /api/refunds/{id}`, `GET /api/refunds/{id}/edit` — all return `{success, data, ...}` JSON. Later tasks (Vue components) call these exact paths and rely on `data` containing the raw `Refund` attributes plus `customer`/`order`/`plus_order`/`merch_order`/`thread_order` relation keys (Laravel's default snake_case relation serialization for `plusOrder()` → `plus_order`, `merchOrder()` → `merch_order`, `threadOrder()` → `thread_order`).

- [ ] **Step 1: Write the controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Models\Order;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{
    private function validationRules()
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'plus_order_id' => 'nullable|exists:plus_orders,id',
            'merch_order_id' => 'nullable|exists:merch_orders,id',
            'thread_order_id' => 'nullable|exists:thread_orders,id',
            'refund_amount' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'payment_type' => 'nullable|string|max:50',
            'cash_journal' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'refunded_at' => 'nullable|date',
        ];
    }

    private function relations()
    {
        return ['customer', 'order', 'plusOrder', 'merchOrder', 'threadOrder'];
    }

    public function index(Request $request)
    {
        $query = Refund::with($this->relations());

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $query->where('refund_id', 'LIKE', "%{$request->search}%");
        }

        $query->orderBy($request->get('order_by', 'created_at'), $request->get('order_direction', 'desc'));

        $results = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    public function orderOptions(Request $request)
    {
        $query = Order::select('id', 'order_id');

        if ($request->filled('search')) {
            $query->where('order_id', 'LIKE', "%{$request->search}%");
        }

        $results = $query->orderByDesc('id')->paginate($request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $results->items()]);
    }

    public function statistics()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_refunds' => Refund::count(),
                'total_refund_amount' => (float) Refund::sum('refund_amount'),
                'refunds_this_month' => Refund::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'average_refund_amount' => (float) Refund::avg('refund_amount'),
            ],
        ]);
    }

    public function show($id)
    {
        $refund = Refund::with($this->relations())->find($id);

        if (!$refund) {
            return response()->json(['success' => false, 'message' => 'Refund not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $refund]);
    }

    public function edit($id)
    {
        $refund = Refund::with($this->relations())->find($id);

        if (!$refund) {
            return response()->json(['success' => false, 'message' => 'Refund not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $refund]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->validationRules());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $refundId = BusinessId::next('refunds', 'refund_id', 'QV-REFD-', 6);

        $refund = Refund::create([
            'refund_id' => $refundId,
            'customer_id' => $request->customer_id,
            'order_id' => $request->order_id,
            'plus_order_id' => $request->plus_order_id,
            'merch_order_id' => $request->merch_order_id,
            'thread_order_id' => $request->thread_order_id,
            'refund_amount' => $request->refund_amount,
            'deposit_amount' => $request->deposit_amount,
            'payment_type' => $request->payment_type,
            'cash_journal' => $request->boolean('cash_journal'),
            'notes' => $request->notes,
            'refunded_at' => $request->refunded_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Refund created successfully',
            'data' => $refund->load($this->relations()),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $refund = Refund::find($id);

        if (!$refund) {
            return response()->json(['success' => false, 'message' => 'Refund not found'], 404);
        }

        $validator = Validator::make($request->all(), $this->validationRules());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $refund->update([
            'customer_id' => $request->customer_id,
            'order_id' => $request->order_id,
            'plus_order_id' => $request->plus_order_id,
            'merch_order_id' => $request->merch_order_id,
            'thread_order_id' => $request->thread_order_id,
            'refund_amount' => $request->refund_amount,
            'deposit_amount' => $request->deposit_amount,
            'payment_type' => $request->payment_type,
            'cash_journal' => $request->boolean('cash_journal'),
            'notes' => $request->notes,
            'refunded_at' => $request->refunded_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Refund updated successfully',
            'data' => $refund->fresh()->load($this->relations()),
        ]);
    }

    public function destroy($id)
    {
        $refund = Refund::find($id);

        if (!$refund) {
            return response()->json(['success' => false, 'message' => 'Refund not found'], 404);
        }

        try {
            $refund->delete();
            return response()->json(['success' => true, 'message' => 'Refund deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete refund', 'error' => $e->getMessage()], 500);
        }
    }
}
```

- [ ] **Step 2: Verify syntax**

```bash
host-spawn docker exec quivitech-im-dev php -l app/Http/Controllers/RefundController.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Add the route block to `routes/api.php`**

Find the end of the existing `thread-orders` block (`Route::prefix('thread-orders')->group(...)`, closes around the `QUIVITHREAD ROUTES` section) and add immediately after it:

```php
/*
|--------------------------------------------------------------------------
| REFUND ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('refunds')->group(function () {
    Route::get('/', 'RefundController@index');
    Route::post('/', 'RefundController@store');
    Route::get('/statistics', 'RefundController@statistics');
    Route::get('/order-options', 'RefundController@orderOptions');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'RefundController@show');
        Route::get('/edit', 'RefundController@edit');
        Route::put('/', 'RefundController@update');
        Route::patch('/', 'RefundController@update');
        Route::delete('/', 'RefundController@destroy');
    });
});
```

- [ ] **Step 4: Verify routes are registered**

```bash
host-spawn docker exec quivitech-im-dev php artisan route:list --path=refunds
```
Expected: 8 rows listing all the routes above with `RefundController@...` actions.

- [ ] **Step 5: Smoke-test the API with curl**

```bash
curl -s -X POST http://127.0.0.1/api/refunds -H "Content-Type: application/json" \
  -d '{"customer_id":1,"refund_amount":25.00,"payment_type":"cash"}' | head -c 500
curl -s http://127.0.0.1/api/refunds | head -c 500
```
Expected: first call returns `{"success":true,"message":"Refund created successfully","data":{"refund_id":"QV-REFD-000001",...}}` (or the next sequential number if Step 4 of Task 1 already consumed `000001` — that record was deleted, so the counter may or may not have advanced depending on whether other `refunds` rows exist); second call returns `{"success":true,"data":[...]}` including that record. (Substitute a real customer `id` if `1` doesn't exist locally.)

- [ ] **Step 6: Update `docs/QuiviTech/API-Routes.md`**

Add a new section (after the existing `## QuiviMerch / QuiviPlus / QuiviThread` section):

```markdown
## Refund (built 2026-07-27, see [[QuiviRefund]])

- `prefix: refunds` → `RefundController` (`refund_id` = `QV-REFD-XXXXXX`, via the shared `BusinessId::next()` helper — see [[Business-ID-Normalization]])
  - `GET /` → `index` (paginated list, `?search=` matches `refund_id`, `?customer_id=` filters)
  - `POST /` → `store`
  - `GET /statistics` → `statistics` (total/this-month/average refund amounts)
  - `GET /order-options?search=` → `orderOptions` (lightweight `order_id`/`id` picker for the main `order` table — QuiviPlus/QuiviMerch/QuiviThread order pickers reuse those modules' own existing `/search` endpoints instead)
  - `GET /{id}`, `GET /{id}/edit`, `PUT|PATCH /{id}`, `DELETE /{id}` → standard show/edit/update/soft-delete
- A refund optionally links to **one** of `order_id` (covers QuiviCraft/Serve/Care, which live on the `order` record itself), `plus_order_id`, `merch_order_id`, `thread_order_id` — not enforced as mutually exclusive at the validation layer, by design (see [[QuiviRefund]]).
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/RefundController.php routes/api.php docs/QuiviTech/API-Routes.md
git commit -m "Add RefundController and refund API routes"
```

---

### Task 3: Vue CRUD pages (index, create, edit) + `routes.js`

**Files:**
- Create: `resources/js/components/refunds/index.vue`
- Create: `resources/js/components/refunds/create.vue`
- Create: `resources/js/components/refunds/edit.vue`
- Modify: `resources/js/routes.js`

**Interfaces:**
- Consumes: `GET /api/refunds` (paginated list), `GET /api/refunds/statistics`, `GET /api/refunds/order-options?search=&per_page=`, `GET /api/plus-orders/search?per_page=`, `GET /api/merch-orders/search?per_page=`, `GET /api/thread-orders/search?per_page=`, `GET /api/customers?per_page=`, `POST /api/refunds`, `GET /api/refunds/{id}/edit`, `PUT /api/refunds/{id}`, `DELETE /api/refunds/{id}` (Task 2's routes; the three `*-orders/search` endpoints already exist in `PlusOrderController@search`/`MerchOrderController@search`/`ThreadOrderController@search`, unchanged by this plan).
- Produces: routes `/refunds`, `/refunds/create`, `/refunds/edit/:id` registered in `resources/js/routes.js`, consumed by Task 4's print link and Task 5's sidebar entry.

- [ ] **Step 1: Write `resources/js/components/refunds/index.vue`**

```vue
<template>
  <div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1"><i class="fas fa-undo-alt text-danger mr-2"></i>Refunds</h2>
        <p class="text-muted mb-0">Customer refund records</p>
      </div>
      <router-link to="/refunds/create" class="btn btn-primary">
        <i class="fas fa-plus-circle mr-2"></i> Add Refund
      </router-link>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-receipt"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Refunds</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.total_refunds || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-danger text-white rounded-circle shadow"><i class="fas fa-dollar-sign"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Total Refunded</h6>
                <span class="h4 font-weight-bold mb-0">RM{{ formatNumber(stats.total_refund_amount) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-calendar-alt"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">This Month</h6>
                <span class="h4 font-weight-bold mb-0">{{ stats.refunds_this_month || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card card-stats h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-chart-bar"></i></div>
              <div class="ml-3">
                <h6 class="card-title text-uppercase text-muted mb-0">Average Refund</h6>
                <span class="h4 font-weight-bold mb-0">RM{{ formatNumber(stats.average_refund_amount) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filters & Search</h5>
        <button
            @click="showFilters = !showFilters"
            class="btn btn-sm btn-outline-secondary"
        >
            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
            {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
        </button>
      </div>
      <transition name="filter-panel">
      <div class="card-body" v-if="showFilters">
        <div class="row">
          <div class="col-md-10">
            <column-search-panel
                :columns="filterColumns"
                v-model="filters"
                :visible="true"
            />
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" @click="resetFilters"><i class="fas fa-redo mr-1"></i> Clear</button>
          </div>
        </div>
      </div>
      </transition>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Refund List</h5>
        <span class="text-muted">Total: {{ total }} records</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center">#</th>
                <th>Refund Code</th>
                <th>Customer</th>
                <th>Linked To</th>
                <th class="text-right">Refund Amount</th>
                <th>Payment Type</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody v-if="loading">
              <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
            </tbody>
            <tbody v-else-if="items.length === 0">
              <tr><td colspan="7" class="text-center py-5"><i class="fas fa-database fa-3x text-muted mb-3"></i><h5 class="text-muted">No refunds found</h5></td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(item, index) in items" :key="item.id">
                <td class="text-center align-middle">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                <td class="align-middle font-weight-bold">{{ item.refund_id }}</td>
                <td class="align-middle">{{ item.customer ? item.customer.full_name : '—' }}</td>
                <td class="align-middle">{{ linkedLabel(item) }}</td>
                <td class="align-middle text-right">RM{{ formatNumber(item.refund_amount) }}</td>
                <td class="align-middle">{{ item.payment_type || '—' }}</td>
                <td class="align-middle text-center">
                  <div class="btn-group">
                    <router-link :to="`/refunds/print/${item.id}`" class="btn btn-sm btn-outline-secondary" title="Print"><i class="fas fa-print"></i></router-link>
                    <router-link :to="`/refunds/edit/${item.id}`" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></router-link>
                    <button class="btn btn-sm btn-outline-danger ml-1" @click="deleteItem(item.id)" title="Delete"><i class="fas fa-trash"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-if="total > 0" class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Page {{ currentPage }} of {{ lastPage }}</small>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: currentPage === 1 }"><button class="page-link" @click="changePage(currentPage - 1)">&laquo;</button></li>
            <li class="page-item" v-for="page in pages" :key="page" :class="{ active: page === currentPage }"><button class="page-link" @click="changePage(page)">{{ page }}</button></li>
            <li class="page-item" :class="{ disabled: currentPage === lastPage }"><button class="page-link" @click="changePage(currentPage + 1)">&raquo;</button></li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';

export default {
  components: { ColumnSearchPanel },
  data() {
    return {
      items: [],
      stats: {},
      loading: true,
      showFilters: false,
      filterColumns: [
        { key: 'search', label: 'Refund Code', type: 'text' },
      ],
      filters: { search: '' },
      currentPage: 1,
      perPage: 15,
      total: 0
    };
  },
  computed: {
    lastPage() {
      return Math.max(1, Math.ceil(this.total / this.perPage));
    },
    pages() {
      const pages = [];
      let start = Math.max(1, this.currentPage - 2);
      let end = Math.min(this.lastPage, this.currentPage + 2);
      for (let i = start; i <= end; i++) pages.push(i);
      return pages;
    }
  },
  watch: {
    filters: {
      handler() {
        this.applyFilters();
      },
      deep: true
    }
  },
  mounted() {
    this.fetchItems();
    this.fetchStatistics();
  },
  methods: {
    formatNumber(value) {
      const num = parseFloat(value) || 0;
      return num.toFixed(2);
    },
    linkedLabel(item) {
      if (item.order) return `Order ${item.order.order_id}`;
      if (item.plus_order) return `Plus Order ${item.plus_order.plus_order_id}`;
      if (item.merch_order) return `Merch Order ${item.merch_order.merch_order_id}`;
      if (item.thread_order) return `Thread Order ${item.thread_order.thread_order_id}`;
      return '—';
    },
    async fetchItems() {
      this.loading = true;
      try {
        const params = { page: this.currentPage, per_page: this.perPage, ...this.filters };
        Object.keys(params).forEach(key => { if (params[key] === '') delete params[key]; });
        const res = await axios.get('/api/refunds', { params });
        this.items = res.data.data || [];
        this.total = res.data.meta ? res.data.meta.total : this.items.length;
      } catch (error) {
        console.error('Error fetching refunds:', error);
        Swal.fire('Error!', 'Failed to load refunds', 'error');
      } finally {
        this.loading = false;
      }
    },
    async fetchStatistics() {
      try {
        const res = await axios.get('/api/refunds/statistics');
        this.stats = res.data.data || {};
      } catch (error) {
        console.error('Error fetching statistics:', error);
      }
    },
    applyFilters() {
      this.currentPage = 1;
      this.fetchItems();
    },
    resetFilters() {
      this.filters = { search: '' };
    },
    changePage(page) {
      if (page < 1 || page > this.lastPage) return;
      this.currentPage = page;
      this.fetchItems();
    },
    deleteItem(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "This will delete the refund.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/api/refunds/${id}`)
            .then(() => {
              Swal.fire('Deleted!', 'Refund has been deleted.', 'success');
              this.fetchItems();
              this.fetchStatistics();
            })
            .catch(() => Swal.fire('Error!', 'Failed to delete refund', 'error'));
        }
      });
    }
  }
};
</script>

<style scoped>
.card-stats { border-radius: 10px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
.icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.table thead th { border-top: none; border-bottom: 2px solid #dee2e6; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
.filter-panel-enter-active,
.filter-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.filter-panel-enter,
.filter-panel-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
```

- [ ] **Step 2: Write `resources/js/components/refunds/create.vue`**

```vue
<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Add Refund</h4>
          <router-link to="/refunds" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <form @submit.prevent="submit">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Customer <span class="text-danger">*</span></label>
                <select v-model="form.customer_id" class="form-control" required>
                  <option value="">Select Customer</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.customer_id }} - {{ c.full_name }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Refunded At</label>
                <input type="datetime-local" v-model="form.refunded_at" class="form-control">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Linked To</label>
                <select v-model="linkType" class="form-control" @change="onLinkTypeChange">
                  <option value="">None</option>
                  <option value="order">Order (Craft/Serve/Care)</option>
                  <option value="plus_order">Plus Order</option>
                  <option value="merch_order">Merch Order</option>
                  <option value="thread_order">Thread Order</option>
                </select>
              </div>
            </div>
            <div class="col-md-8" v-if="linkType">
              <div class="form-group">
                <label class="form-label">{{ linkTypeLabel }} Record</label>
                <select v-model="linkId" class="form-control">
                  <option value="">Select {{ linkTypeLabel }}</option>
                  <option v-for="opt in linkOptions" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Refund Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" v-model="form.refund_amount" class="form-control" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Deposit Made</label>
                <input type="number" step="0.01" min="0" v-model="form.deposit_amount" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Payment Type</label>
                <input type="text" v-model="form.payment_type" class="form-control" placeholder="e.g. Cash, Bank Transfer">
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" id="cash-journal" v-model="form.cash_journal">
              <label class="custom-control-label" for="cash-journal">Cash Journal</label>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Create Refund
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

const LINK_LABELS = {
  order: 'Order',
  plus_order: 'Plus Order',
  merch_order: 'Merch Order',
  thread_order: 'Thread Order',
};

const LINK_ENDPOINTS = {
  order: { url: '/api/refunds/order-options', idKey: 'id', labelFn: o => o.order_id },
  plus_order: { url: '/api/plus-orders/search', idKey: 'id', labelFn: o => o.plus_order_id },
  merch_order: { url: '/api/merch-orders/search', idKey: 'id', labelFn: o => o.merch_order_id },
  thread_order: { url: '/api/thread-orders/search', idKey: 'id', labelFn: o => o.thread_order_id },
};

const LINK_FORM_KEYS = {
  order: 'order_id',
  plus_order: 'plus_order_id',
  merch_order: 'merch_order_id',
  thread_order: 'thread_order_id',
};

export default {
  data() {
    return {
      customers: [],
      linkType: '',
      linkId: '',
      linkOptions: [],
      form: {
        customer_id: '',
        order_id: '',
        plus_order_id: '',
        merch_order_id: '',
        thread_order_id: '',
        refund_amount: '',
        deposit_amount: '',
        payment_type: '',
        cash_journal: false,
        notes: '',
        refunded_at: '',
      },
      loading: false,
      errors: []
    };
  },
  computed: {
    linkTypeLabel() {
      return LINK_LABELS[this.linkType] || '';
    }
  },
  watch: {
    linkId(newVal) {
      Object.values(LINK_FORM_KEYS).forEach(key => { this.form[key] = ''; });
      if (this.linkType && newVal) {
        this.form[LINK_FORM_KEYS[this.linkType]] = newVal;
      }
    }
  },
  mounted() {
    this.fetchCustomers();
  },
  methods: {
    async fetchCustomers() {
      try {
        const res = await axios.get('/api/customers', { params: { per_page: 1000 } });
        this.customers = res.data.data || res.data;
      } catch (error) {
        console.error('Error fetching customers:', error);
      }
    },
    async onLinkTypeChange() {
      this.linkId = '';
      this.linkOptions = [];
      if (!this.linkType) return;
      const endpoint = LINK_ENDPOINTS[this.linkType];
      try {
        const res = await axios.get(endpoint.url, { params: { per_page: 1000 } });
        const raw = res.data.data || [];
        this.linkOptions = raw.map(o => ({ id: o[endpoint.idKey], label: endpoint.labelFn(o) }));
      } catch (error) {
        console.error('Error fetching link options:', error);
      }
    },
    submit() {
      this.loading = true;
      this.errors = [];

      axios.post('/api/refunds', this.form)
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Refund created successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/refunds'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to create refund');
          }
          Swal.fire('Error!', this.errors.join('<br>'), 'error');
        })
        .finally(() => { this.loading = false; });
    }
  }
};
</script>

<style scoped>
.form-card { border-radius: 10px; border: none; }
.form-label { font-weight: 600; color: #495057; }
</style>
```

- [ ] **Step 3: Write `resources/js/components/refunds/edit.vue`**

Same structure as `create.vue`, adapted to load an existing record and `PUT` instead of `POST`:

```vue
<template>
  <div class="container my-5">
    <div class="card shadow-sm form-card">
      <div class="card-header bg-warning text-dark">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><i class="fas fa-edit mr-2"></i>Edit Refund</h4>
          <router-link to="/refunds" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
        </div>
      </div>
      <div class="card-body">
        <div v-if="loadingData" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>
        <form v-else @submit.prevent="submit">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Customer <span class="text-danger">*</span></label>
                <select v-model="form.customer_id" class="form-control" required>
                  <option value="">Select Customer</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.customer_id }} - {{ c.full_name }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Refunded At</label>
                <input type="datetime-local" v-model="form.refunded_at" class="form-control">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Linked To</label>
                <select v-model="linkType" class="form-control" @change="onLinkTypeChange">
                  <option value="">None</option>
                  <option value="order">Order (Craft/Serve/Care)</option>
                  <option value="plus_order">Plus Order</option>
                  <option value="merch_order">Merch Order</option>
                  <option value="thread_order">Thread Order</option>
                </select>
              </div>
            </div>
            <div class="col-md-8" v-if="linkType">
              <div class="form-group">
                <label class="form-label">{{ linkTypeLabel }} Record</label>
                <select v-model="linkId" class="form-control">
                  <option value="">Select {{ linkTypeLabel }}</option>
                  <option v-for="opt in linkOptions" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Refund Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" v-model="form.refund_amount" class="form-control" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Deposit Made</label>
                <input type="number" step="0.01" min="0" v-model="form.deposit_amount" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="form-label">Payment Type</label>
                <input type="text" v-model="form.payment_type" class="form-control" placeholder="e.g. Cash, Bank Transfer">
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" id="cash-journal-edit" v-model="form.cash_journal">
              <label class="custom-control-label" for="cash-journal-edit">Cash Journal</label>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
          </div>

          <div v-if="errors.length > 0" class="alert alert-danger mt-3">
            <ul class="mb-0 pl-3"><li v-for="error in errors" :key="error">{{ error }}</li></ul>
          </div>

          <div class="form-actions mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
              <i v-else class="fas fa-save mr-2"></i> Update Refund
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

const LINK_LABELS = {
  order: 'Order',
  plus_order: 'Plus Order',
  merch_order: 'Merch Order',
  thread_order: 'Thread Order',
};

const LINK_ENDPOINTS = {
  order: { url: '/api/refunds/order-options', idKey: 'id', labelFn: o => o.order_id },
  plus_order: { url: '/api/plus-orders/search', idKey: 'id', labelFn: o => o.plus_order_id },
  merch_order: { url: '/api/merch-orders/search', idKey: 'id', labelFn: o => o.merch_order_id },
  thread_order: { url: '/api/thread-orders/search', idKey: 'id', labelFn: o => o.thread_order_id },
};

const LINK_FORM_KEYS = {
  order: 'order_id',
  plus_order: 'plus_order_id',
  merch_order: 'merch_order_id',
  thread_order: 'thread_order_id',
};

export default {
  data() {
    return {
      customers: [],
      linkType: '',
      linkId: '',
      linkOptions: [],
      suppressLinkWatch: false,
      form: {
        customer_id: '', order_id: '', plus_order_id: '', merch_order_id: '', thread_order_id: '',
        refund_amount: '', deposit_amount: '', payment_type: '', cash_journal: false, notes: '', refunded_at: '',
      },
      loading: false,
      loadingData: true,
      errors: []
    };
  },
  computed: {
    linkTypeLabel() {
      return LINK_LABELS[this.linkType] || '';
    }
  },
  watch: {
    linkId(newVal) {
      if (this.suppressLinkWatch) return;
      Object.values(LINK_FORM_KEYS).forEach(key => { this.form[key] = ''; });
      if (this.linkType && newVal) {
        this.form[LINK_FORM_KEYS[this.linkType]] = newVal;
      }
    }
  },
  mounted() {
    this.fetchCustomers();
    this.fetchEditData();
  },
  methods: {
    async fetchCustomers() {
      try {
        const res = await axios.get('/api/customers', { params: { per_page: 1000 } });
        this.customers = res.data.data || res.data;
      } catch (error) {
        console.error('Error fetching customers:', error);
      }
    },
    async onLinkTypeChange() {
      this.linkId = '';
      this.linkOptions = [];
      if (!this.linkType) return;
      const endpoint = LINK_ENDPOINTS[this.linkType];
      try {
        const res = await axios.get(endpoint.url, { params: { per_page: 1000 } });
        const raw = res.data.data || [];
        this.linkOptions = raw.map(o => ({ id: o[endpoint.idKey], label: endpoint.labelFn(o) }));
      } catch (error) {
        console.error('Error fetching link options:', error);
      }
    },
    async fetchEditData() {
      this.loadingData = true;
      try {
        const res = await axios.get(`/api/refunds/${this.$route.params.id}/edit`);
        const record = res.data.data;
        this.form = {
          customer_id: record.customer_id,
          order_id: record.order_id || '',
          plus_order_id: record.plus_order_id || '',
          merch_order_id: record.merch_order_id || '',
          thread_order_id: record.thread_order_id || '',
          refund_amount: record.refund_amount,
          deposit_amount: record.deposit_amount || '',
          payment_type: record.payment_type || '',
          cash_journal: Boolean(record.cash_journal),
          notes: record.notes || '',
          refunded_at: record.refunded_at ? record.refunded_at.substring(0, 16) : '',
        };

        if (record.order_id) {
          this.linkType = 'order';
          await this.onLinkTypeChangePreserving();
          this.setLinkIdSilently(record.order_id);
        } else if (record.plus_order_id) {
          this.linkType = 'plus_order';
          await this.onLinkTypeChangePreserving();
          this.setLinkIdSilently(record.plus_order_id);
        } else if (record.merch_order_id) {
          this.linkType = 'merch_order';
          await this.onLinkTypeChangePreserving();
          this.setLinkIdSilently(record.merch_order_id);
        } else if (record.thread_order_id) {
          this.linkType = 'thread_order';
          await this.onLinkTypeChangePreserving();
          this.setLinkIdSilently(record.thread_order_id);
        }
      } catch (error) {
        console.error('Error fetching refund:', error);
        Swal.fire('Error!', 'Failed to load refund', 'error').then(() => this.$router.push('/refunds'));
      } finally {
        this.loadingData = false;
      }
    },
    async onLinkTypeChangePreserving() {
      this.linkOptions = [];
      if (!this.linkType) return;
      const endpoint = LINK_ENDPOINTS[this.linkType];
      try {
        const res = await axios.get(endpoint.url, { params: { per_page: 1000 } });
        const raw = res.data.data || [];
        this.linkOptions = raw.map(o => ({ id: o[endpoint.idKey], label: endpoint.labelFn(o) }));
      } catch (error) {
        console.error('Error fetching link options:', error);
      }
    },
    setLinkIdSilently(id) {
      this.suppressLinkWatch = true;
      this.linkId = id;
      this.$nextTick(() => { this.suppressLinkWatch = false; });
    },
    submit() {
      this.loading = true;
      this.errors = [];

      axios.put(`/api/refunds/${this.$route.params.id}`, this.form)
        .then(() => {
          Swal.fire({ title: 'Success!', text: 'Refund updated successfully', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => this.$router.push('/refunds'));
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            for (const field in validationErrors) {
              this.errors.push(`${field}: ${validationErrors[field].join(', ')}`);
            }
          } else {
            this.errors.push(error.response?.data?.message || 'Failed to update refund');
          }
          Swal.fire('Error!', this.errors.join('<br>'), 'error');
        })
        .finally(() => { this.loading = false; });
    }
  }
};
</script>

<style scoped>
.form-card { border-radius: 10px; border: none; }
.form-label { font-weight: 600; color: #495057; }
</style>
```

- [ ] **Step 4: Register routes in `resources/js/routes.js`**

Add near the other `require` statements (after the `thread orders` block, e.g. around line 190):

```js
//refunds
let refunds = require('./components/refunds/index.vue').default;
let refundscreate = require('./components/refunds/create.vue').default;
let refundsedit = require('./components/refunds/edit.vue').default;
```

Add near the other path entries (after the `thread-orders` path block):

```js
      // refunds
      { path: '/refunds', component: refunds, name: 'refunds', meta: { layout: 'app' } },
      { path: '/refunds/create', component: refundscreate, name: 'refundscreate', meta: { layout: 'app' } },
      { path: '/refunds/edit/:id', component: refundsedit, name: 'refundsedit', meta: { layout: 'app' } },
```

- [ ] **Step 5: Build and verify**

```bash
cd /home/penyahpepijat/claude/inventory-management
nvm use 12 && npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: webpack build completes with no errors, `public/js/app.js` updated. Then load `http://127.0.0.1/refunds` in a browser (or `curl -s http://127.0.0.1/refunds | grep -o '<title>.*</title>'` to confirm the SPA shell responds, since the route itself renders client-side) and manually verify: the list loads (empty state or the record from Task 2's curl test), "Add Refund" opens the create form, selecting a "Linked To" type populates the second dropdown, and submitting creates a record that appears back in the list.

- [ ] **Step 6: Commit**

```bash
git add resources/js/components/refunds/index.vue resources/js/components/refunds/create.vue resources/js/components/refunds/edit.vue resources/js/routes.js public/js/app.js public/mix-manifest.json
git commit -m "Add refund list/create/edit Vue pages"
```

---

### Task 4: Print view + `Frontend-Components.md`

**Files:**
- Create: `resources/js/components/refunds/print.vue`
- Modify: `resources/js/routes.js`
- Modify: `docs/QuiviTech/Frontend-Components.md`

**Interfaces:**
- Consumes: `GET /api/refunds/{id}` (Task 2's `show` route, returns the full `Refund` with `customer`/`order`/`plus_order`/`merch_order`/`thread_order` relations).
- Produces: route `/refunds/print/:id`, linked from Task 3's `index.vue` "Print" button (`router-link :to="/refunds/print/${item.id}"`).

- [ ] **Step 1: Write `resources/js/components/refunds/print.vue`**

```vue
<template>
  <div class="container my-5 print-receipt">
    <div class="d-flex justify-content-between mb-3 no-print">
      <router-link to="/refunds" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</router-link>
      <button class="btn btn-primary btn-sm" @click="print" :disabled="loading"><i class="fas fa-print mr-1"></i> Print</button>
    </div>

    <div v-if="loading" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>

    <div v-else class="card shadow-sm receipt-card">
      <div class="card-body">
        <div class="row align-items-start mb-4 pb-3 letterhead">
          <div class="col-6">
            <h4 class="mb-0 font-weight-bold">QuiviTech Enterprise</h4>
            <small class="text-muted d-block">Sunway Damansara, 47810 Petaling Jaya, Selangor</small>
            <small class="text-muted d-block">support@quivitech.com</small>
            <small class="text-muted d-block">+0197017420</small>
          </div>
          <div class="col-6 text-right">
            <h5 class="text-uppercase text-muted mb-2">Refund</h5>
            <div><small class="text-muted">Date:</small> {{ formatDate(refund.refunded_at || refund.created_at) }}</div>
            <div><small class="text-muted">Refund No.:</small> {{ refund.refund_id }}</div>
          </div>
        </div>

        <div class="mb-4">
          <small class="text-uppercase font-weight-bold text-muted d-block mb-1">Bill To</small>
          <div>{{ refund.customer ? refund.customer.full_name : '—' }}</div>
        </div>

        <table class="table table-bordered mb-4">
          <thead class="thead-light">
            <tr>
              <th>Quivi ID</th>
              <th>Description</th>
              <th class="text-center">Qty</th>
              <th class="text-right">Unit Price</th>
              <th class="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>{{ refund.refund_id }}</td>
              <td>{{ linkedDescription }}</td>
              <td class="text-center">1</td>
              <td class="text-right">RM{{ formatNumber(refund.refund_amount) }}</td>
              <td class="text-right">RM{{ formatNumber(refund.refund_amount) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="row">
          <div class="col-md-7"></div>
          <div class="col-md-5">
            <table class="table table-sm mb-0">
              <tbody>
                <tr><td>Subtotal</td><td class="text-right">RM{{ formatNumber(refund.refund_amount) }}</td></tr>
                <tr><td>Discount</td><td class="text-right">RM0.00</td></tr>
                <tr><td>Subtotal Less Discount</td><td class="text-right">RM{{ formatNumber(refund.refund_amount) }}</td></tr>
                <tr><td>Tax Rate</td><td class="text-right">3%</td></tr>
                <tr><td>Total Tax</td><td class="text-right">RM0.00</td></tr>
                <tr><td>Shipping/Handling</td><td class="text-right">RM0.00</td></tr>
                <tr><td>Deposit Made</td><td class="text-right">RM{{ formatNumber(refund.deposit_amount) }}</td></tr>
                <tr class="font-weight-bold"><td>Refund Amount</td><td class="text-right">RM{{ formatNumber(refund.refund_amount) }}</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="refund.notes" class="mt-4 pt-3 border-top">
          <small class="text-uppercase font-weight-bold text-muted d-block mb-1">Notes</small>
          <div>{{ refund.notes }}</div>
        </div>
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
      refund: {},
      loading: true,
    };
  },
  computed: {
    linkedDescription() {
      const r = this.refund;
      if (r.order) return `Refund against Order ${r.order.order_id}`;
      if (r.plus_order) return `Refund against Plus Order ${r.plus_order.plus_order_id}`;
      if (r.merch_order) return `Refund against Merch Order ${r.merch_order.merch_order_id}`;
      if (r.thread_order) return `Refund against Thread Order ${r.thread_order.thread_order_id}`;
      return 'Refund';
    }
  },
  mounted() {
    this.fetchRefund();
  },
  methods: {
    formatNumber(value) {
      const num = parseFloat(value) || 0;
      return num.toFixed(2);
    },
    formatDate(value) {
      if (!value) return '—';
      return new Date(value).toLocaleDateString();
    },
    async fetchRefund() {
      this.loading = true;
      try {
        const res = await axios.get(`/api/refunds/${this.$route.params.id}`);
        this.refund = res.data.data;
      } catch (error) {
        console.error('Error fetching refund:', error);
        Swal.fire('Error!', 'Failed to load refund', 'error').then(() => this.$router.push('/refunds'));
      } finally {
        this.loading = false;
      }
    },
    print() {
      window.print();
    }
  }
};
</script>

<style scoped>
.receipt-card { border: none; border-radius: 10px; }
.letterhead { border-bottom: 4px solid #f5deb3; }
@media print {
  .no-print { display: none !important; }
}
</style>
```

- [ ] **Step 2: Register the route in `resources/js/routes.js`**

Add to the `refunds` require block from Task 3:

```js
let refundsprint = require('./components/refunds/print.vue').default;
```

Add to the `refunds` path block from Task 3:

```js
      { path: '/refunds/print/:id', component: refundsprint, name: 'refundsprint', meta: { layout: 'app' } },
```

- [ ] **Step 3: Build and verify**

```bash
cd /home/penyahpepijat/claude/inventory-management
nvm use 12 && npx cross-env NODE_ENV=development node_modules/webpack/bin/webpack.js --config=node_modules/laravel-mix/setup/webpack.config.js
```
Expected: build succeeds. Manually verify: clicking "Print" on a refund row in `/refunds` navigates to `/refunds/print/:id` and renders the letterhead/breakdown with the correct refund/deposit amounts; the browser's print dialog opens on clicking the in-page "Print" button and the `.no-print` back/print buttons are hidden in the print preview.

- [ ] **Step 4: Update `docs/QuiviTech/Frontend-Components.md`**

Add an entry (following the existing per-module list format in that file):

```markdown
- **`resources/js/components/refunds/`** — `index.vue` (list + stat cards + delete), `create.vue`/`edit.vue` (customer + an optional single "Linked To" picker across Order/Plus Order/Merch Order/Thread Order, since a refund can attach to at most one), `print.vue` (letterhead receipt view, `window.print()` only — no PDF library). See [[QuiviRefund]].
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/refunds/print.vue resources/js/routes.js public/js/app.js public/mix-manifest.json docs/QuiviTech/Frontend-Components.md
git commit -m "Add refund print/receipt view"
```

---

### Task 5: Sidebar menu entry + vault documentation

**Files:**
- Create: `database/migrations/2026_07_28_160000_add_refunds_menu_item.php`
- Create: `docs/QuiviTech/QuiviRefund.md`
- Modify: `docs/QuiviTech/General.md`
- Modify: `docs/QuiviTech/QuiviPlus.md`
- Modify: `docs/QuiviTech/Work-In-Progress.md`

**Interfaces:**
- Consumes: `App\Models\MenuItem` (`parent_id`, `type`, `label`, `icon`, `route`, `sort_order`, `divider_before`, `is_active` — existing model, unchanged) and the live top-level `sort_order` values (`Dashboard`=0 ... `Stock`=12 is currently the highest, confirmed against the running dev DB), plus the routes registered in Tasks 3/4 (`/refunds`, `/refunds/create`).
- Produces: two new `menu_items` rows (a top-level `group` "Refund" + one `header`/`link` pair) rendered by the existing `resources/views/partials/sidebar-menu-item.blade.php` recursive partial — no Blade/partial changes needed, since that partial already renders whatever's in the `menu_items` table.

- [ ] **Step 1: Confirm the current highest top-level `sort_order`**

```bash
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
echo \App\Models\MenuItem::whereNull('parent_id')->max('sort_order');
"
```
Expected: `12` (matches `Stock`, the current last top-level group, per the discovery during planning that live `menu_items` already has Stock reorganized outside the `MenuItemsTableSeeder.php` file — see Step 4 below). If this returns a different number, use `<that number> + 1` as the `sort_order` in Step 2 instead of `13`.

- [ ] **Step 2: Write the additive migration**

This inserts new rows directly via `MenuItem::create()` rather than editing `database/seeds/MenuItemsTableSeeder.php` and re-running it — that seeder's `run()` starts with `MenuItem::query()->delete()` and would wipe the live "Stock" reorg it doesn't know about (see Global Constraints). `down()` removes exactly the rows this migration adds, by label, so it stays safely reversible without touching anything else in the table.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\MenuItem;

class AddRefundsMenuItem extends Migration
{
    public function up()
    {
        $group = MenuItem::create([
            'parent_id' => null,
            'type' => 'group',
            'label' => 'Refund',
            'icon' => 'fas fa-fw fa-undo-alt',
            'route' => null,
            'sort_order' => 13,
            'divider_before' => false,
            'is_active' => true,
        ]);

        $header = MenuItem::create([
            'parent_id' => $group->id,
            'type' => 'header',
            'label' => 'Refund Management',
            'icon' => null,
            'route' => null,
            'sort_order' => 0,
            'divider_before' => false,
            'is_active' => true,
        ]);

        MenuItem::create([
            'parent_id' => $header->id,
            'type' => 'link',
            'label' => 'All Refunds',
            'icon' => null,
            'route' => '/refunds',
            'sort_order' => 0,
            'divider_before' => false,
            'is_active' => true,
        ]);

        MenuItem::create([
            'parent_id' => $header->id,
            'type' => 'link',
            'label' => 'Add Refund',
            'icon' => null,
            'route' => '/refunds/create',
            'sort_order' => 1,
            'divider_before' => false,
            'is_active' => true,
        ]);
    }

    public function down()
    {
        MenuItem::where('label', 'Refund')->whereNull('parent_id')->each(function ($group) {
            MenuItem::where('parent_id', $group->id)->each(function ($header) {
                MenuItem::where('parent_id', $header->id)->delete();
                $header->delete();
            });
            $group->delete();
        });
    }
}
```

- [ ] **Step 3: Run the migration and verify**

```bash
host-spawn docker exec quivitech-im-dev php artisan migrate --path=database/migrations/2026_07_28_160000_add_refunds_menu_item.php
host-spawn docker exec quivitech-im-dev php artisan tinker --execute="
\$g = \App\Models\MenuItem::where('label','Refund')->whereNull('parent_id')->first();
echo \$g->children->first()->children->pluck('label','route')->toJson();
"
```
Expected: migration reports "Migrated"; tinker prints something like `{"\/refunds":"All Refunds","\/refunds\/create":"Add Refund"}`. Then load the app in a browser and confirm a new "Refund" item with an undo icon appears in the sidebar, expands to show "All Refunds"/"Add Refund", and both navigate correctly.

- [ ] **Step 4: Write `docs/QuiviTech/QuiviRefund.md`**

```markdown
---
tags: [module, refund]
---

# QuiviRefund

Refund records for customer refunds — built 2026-07-27 from two reference documents the user supplied: a printable refund receipt template and a `QVT_REF` "Overview V2"-style data spec (the same source-spreadsheet convention already used for [[QuiviMerch]]/[[QuiviPlus]]/[[QuiviThread]]).

## Model

- **`Refund`** (`refund_id` = `QV-REFD-XXXXXX`, via the shared `BusinessId::next()` helper — see [[Business-ID-Normalization]], unlike QuiviMerch/Plus/Thread's `Model::count() + 1` scheme): `customer_id` required; an optional link to **one** of `order_id` (covers QuiviCraft/Serve/Care, which already live on the `order` record itself — not separate columns, unlike the source spec's `QVCR`/`QVSE`/`QVCA` columns), `plus_order_id`, `merch_order_id`, `thread_order_id` (not enforced as mutually exclusive at the validation layer — a deliberate simplification, see the design spec); `refund_amount` (required), `deposit_amount` (a plain nullable decimal — there's no `deposits` table anywhere in this app, only this field), `payment_type` (free text), `cash_journal` (boolean), `notes`, `refunded_at`.

## Note: QuiviPlus's "no refunds" policy is not enforced here

[[QuiviPlus]] is documented as "always paid, no refunds per the Overview V2 spec" — yet the source `QVT_REF` spec itself has a `QVPL ID` column, and `refunds.plus_order_id` exists for schema completeness to match it. Nothing in `RefundController`'s validation stops a refund from linking to a Plus order; keeping that policy is a business-process decision, not something this module enforces.

## No line items, no PDF export

The source `QVT_REF` spec has a single `Refund Amount` column per row (no line items), so `refunds` has one `refund_amount` field rather than a child items table like `plus_order_items`/`merch_order_items`. The print view (`resources/js/components/refunds/print.vue`) replicates the reference receipt's Subtotal/Tax/Deposit breakdown from that single amount, and uses the browser's native `window.print()` — no PDF library.

## Related
- [[QuiviPlus]]
- [[QuiviMerch]]
- [[QuiviThread]]
- [[Domain-Models]]
- [[API-Routes]]
- [[Business-ID-Normalization]]
```

- [ ] **Step 5: Link it from `docs/QuiviTech/General.md`**

Add a line alongside the existing `QuiviMerch`/`QuiviPlus`/`QuiviThread` links (same list found at `General.md` around the module-index section):

```markdown
- [[QuiviRefund]] — customer refund records (2026-07-27)
```

- [ ] **Step 6: Cross-link from `docs/QuiviTech/QuiviPlus.md`**

Add a line to the `## Related` section:

```markdown
- [[QuiviRefund]] — the refund schema exists and can technically link to a QuiviPlus order despite this module's "no refunds" policy; see [[QuiviRefund]] for why that's not enforced
```

- [ ] **Step 7: Document the seeder-staleness landmine in `docs/QuiviTech/Work-In-Progress.md`**

Add a new section (following the file's existing `## Known bug: ...` heading style, placed before the `## Related` section at the end):

```markdown
## Known gap: `MenuItemsTableSeeder.php` is stale relative to the live sidebar (found 2026-07-27)

`database/seeds/MenuItemsTableSeeder.php`'s `run()` starts with `MenuItem::query()->delete()` and rebuilds the entire sidebar from its own hardcoded `$tree` array — but that array has no `Stock` top-level group at all, while the live `menu_items` table does (with an icon fix and items moved out of `Inventory`, shipped directly via `quivi.sql`/DB in commit `1945073` — "Stock menu icon fix + reorg" — which never touched this seeder file). Re-running `php artisan db:seed --class=MenuItemsTableSeeder` today would silently revert that reorg. Found while adding the [[QuiviRefund]] sidebar entry, which was done as a standalone additive migration (`2026_07_28_160000_add_refunds_menu_item.php`) instead of editing this seeder, specifically to avoid the same trap. Not yet fixed — fixing it means back-porting the live `Stock` group's actual shape (query `menu_items` for its current `parent_id`/children) into the seeder's `$tree` array, which nobody has done.
```

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_07_28_160000_add_refunds_menu_item.php docs/QuiviTech/QuiviRefund.md docs/QuiviTech/General.md docs/QuiviTech/QuiviPlus.md docs/QuiviTech/Work-In-Progress.md
git commit -m "Add refund sidebar entry and QuiviRefund vault documentation"
```

---

## Final verification (after all tasks)

- [ ] Run `host-spawn docker exec quivitech-im-dev php -l` against every new/modified PHP file (`RefundController.php`, both new migrations, `Refund.php`) — expect "No syntax errors detected" for each.
- [ ] Confirm `host-spawn docker exec quivitech-im-dev php artisan migrate:status` shows both new migrations as `Ran`.
- [ ] Full manual walkthrough: create a refund linked to each of the 4 link types (Order/Plus Order/Merch Order/Thread Order) plus one with no link, confirm each shows correctly in the list's "Linked To" column, edit one to change its link type, print one and confirm the receipt renders the right amounts, delete one and confirm it disappears from the list.
- [ ] Confirm the sidebar "Refund" entry appears and both links navigate correctly.
