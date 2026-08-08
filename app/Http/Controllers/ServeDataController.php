<?php

namespace App\Http\Controllers;

use App\Models\ServeData;
use App\Models\Serves;
use App\Models\Customer;
use App\Models\Order;
use App\Models\InvMerch;
use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ServeDataController extends Controller
{
    use FiltersSortsAndPaginates;

    public function index(Request $request)
    {
        $query = ServeData::with(['customer', 'order', 'serve']);

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('serve_data.serve_id', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('serve_data.qvse_cid', 'LIKE', '%' . $escaped . '%')
                    ->orWhereHas('customer', function ($q2) use ($escaped) {
                        $q2->where('full_name', 'LIKE', '%' . $escaped . '%')
                            ->orWhere('customer_id', 'LIKE', '%' . $escaped . '%');
                    })
                    ->orWhereHas('order', function ($q2) use ($escaped) {
                        $q2->where('order_id', 'LIKE', '%' . $escaped . '%');
                    });
            });
        }

        $status = $request->input('status');
        if ($status === 'active') {
            $query->where('start_serve_enabled', true);
        } elseif ($status === 'not_started') {
            $query->where('start_serve_enabled', false);
        } elseif ($status === 'with_upgrade') {
            $query->where('upgrade_pce_enabled', true);
        }

        $this->applyEqualsFilter($query, $request, 'customer_id', 'serve_data.customer_id');
        $this->applyEqualsFilter($query, $request, 'lkp_serve_id', 'serve_data.lkp_serve_id');

        $dateFrom = $request->input('date_from');
        if (is_scalar($dateFrom) && $dateFrom !== '') {
            $query->whereDate('serve_data.created_at', '>=', $dateFrom);
        }

        $sortBy = $request->input('sort_by');
        $sortDir = $request->input('sort_dir');

        if ($sortBy === 'customer_name') {
            $dir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc';
            $query->join('customers', 'serve_data.customer_id', '=', 'customers.id')
                ->select('serve_data.*')
                ->orderBy('customers.full_name', $dir)
                ->orderBy('serve_data.id', $dir);
        } else {
            $this->resolveSortAndApply($query, $request, ['created_at', 'serve_id'], 'created_at', 'id', [], 'desc');
        }

        if ($request->has('export') && $request->export === 'csv') {
            return $this->exportToCSV($query->get());
        }

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    public function show($id)
    {
        $serveData = ServeData::with(['customer', 'order', 'serve'])->find($id);

        if (!$serveData) {
            return response()->json([
                'success' => false,
                'message' => 'Serve data not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $serveData
        ]);
    }

    public function edit($id)
    {
        $serveData = ServeData::with(['customer', 'order', 'serve'])->find($id);

        if (!$serveData) {
            return response()->json([
                'success' => false,
                'message' => 'Serve data not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $serveData
        ]);
    }

    // Create new serve data
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'order_id' => 'required',
            'lkp_serve_id' => 'required',
            'start_serve_enabled' => 'boolean',
            'start_serve_date' => 'nullable|date_format:Y-m-d H:i:s',
            'start_serve_timestamp' => 'nullable|integer',
            'upgrade_pce_enabled' => 'boolean',
            'upgrade_pce_notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Generate serve ID — QV-SERV- per the global ID registry (Serve Tier prefix)
            $totalServes = ServeData::count();
            $nextId = $totalServes + 1;
            $serveNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
            $serveId = "QV-SERV-{$serveNumber}";

            // Get serve type for QVSE CID generation
            $serveType = Serves::find($request->lkp_serve_id);
            $serveTypeCode = $serveType ? strtoupper(substr($serveType->code, 0)) : 'GEN';
            // $monthYear = date('my'); // Changed from 2304 format

            // Find next sequence for this serve type
            $lastServe = ServeData::where('lkp_serve_id', $request->lkp_serve_id)
                ->orderBy('id', 'desc')
                ->first();

            $sequence = $lastServe ?
                intval(substr($lastServe->qvse_cid, -4)) + 1 : 1;
            $sequenceNumber = str_pad($sequence, 4, '0', STR_PAD_LEFT);

            $qvseCid = "{$serveTypeCode}-{$sequenceNumber}"; // Changed format

            // Create serve data with NEW field names
            $serveData = ServeData::create([
                'serve_id' => $serveId,
                'qvse_cid' => $qvseCid,
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'lkp_serve_id' => $request->lkp_serve_id,
                'start_serve_enabled' => $request->start_serve_enabled ?? 0,
                'start_serve_date' => $request->start_serve_date,
                'start_serve_timestamp' => $request->start_serve_timestamp,
                'upgrade_pce_enabled' => $request->upgrade_pce_enabled ?? 0,
                'upgrade_pce_notes' => $request->upgrade_pce_notes
            ]);

            DB::commit();

            // Load relationships
            $serveData->load(['customer', 'order', 'serve']);

            return response()->json([
                'success' => true,
                'message' => 'Serve data created successfully',
                'data' => $serveData
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create serve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update serve data
    public function update(Request $request, $id)
    {
        $serveData = ServeData::find($id);

        if (!$serveData) {
            return response()->json([
                'success' => false,
                'message' => 'Serve data not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'order_id' => 'required',
            'lkp_serve_id' => 'required',
            'start_serve_enabled' => 'boolean',
            'start_serve_date' => 'nullable|date_format:Y-m-d H:i:s',
            'start_serve_timestamp' => 'nullable|integer',
            'upgrade_pce_enabled' => 'boolean',
            'upgrade_pce_notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update serve data
            $serveData->update([
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'lkp_serve_id' => $request->lkp_serve_id,
                'start_serve_enabled' => $request->start_serve_enabled ?? 0,
                'start_serve_date' => $request->start_serve_date,
                'start_serve_timestamp' => $request->start_serve_timestamp,
                'upgrade_pce_enabled' => $request->upgrade_pce_enabled ?? 0,
                'upgrade_pce_notes' => $request->upgrade_pce_notes
            ]);

            // Keep the order-level Upgrade PCE fields (used by pos/index.vue
            // and order/edit.vue) in sync with this record -- the reverse
            // direction of OrderController::updateOrderDetails()'s sync into
            // ServeData, so either edit page stays consistent with the other.
            Order::where('id', $serveData->order_id)->update([
                'upgrade_pce_enabled' => $serveData->upgrade_pce_enabled,
                'upgrade_pce_notes' => $serveData->upgrade_pce_notes,
            ]);

            // Load relationships
            $serveData->load(['customer', 'order', 'serve']);

            return response()->json([
                'success' => true,
                'message' => 'Serve data updated successfully',
                'data' => $serveData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update serve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete serve data
    public function destroy($id)
    {
        $serveData = ServeData::find($id);

        if (!$serveData) {
            return response()->json([
                'success' => false,
                'message' => 'Serve data not found'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $skus = $this->serveTierSkus($serveData->lkp_serve_id);
            InvMerch::whereIn('sku_code', $skus)->increment('current_stock', 1);

            $serveData->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Serve data deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete serve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function serveTierSkus(int $lkpServeId): array
    {
        if ($lkpServeId === 1) {
            $skus = ['QVSKU 0001', 'QVSKU 0012'];
        } elseif ($lkpServeId === 2) {
            $skus = ['QVSKU 0002', 'QVSKU 0013'];
        } else {
            $skus = ['QVSKU 0003', 'QVSKU 0014', 'QVSKU 0011'];
        }
        $skus[] = 'QVSKU 0004';
        return $skus;
    }

    // Get statistics
    public function statistics()
    {
        $totalServes = ServeData::count();
        $todayServes = ServeData::whereDate('created_at', today())->count();
        $totalUpgrades = ServeData::where('upgrade_pce_enabled', 1)->count();
        $uniqueCustomers = ServeData::distinct('customer_id')->count('customer_id');

        // Calculate total revenue from orders
        $totalRevenue = ServeData::join('order', 'serve_data.order_id', '=', 'order.id')->sum('order.total');
        return response()->json([
            'success' => true,
            'data' => [
                'total_serves' => $totalServes,
                'today_serves' => $todayServes,
                'total_upgrades' => $totalUpgrades,
                'unique_customers' => $uniqueCustomers,
                'total_revenue' => $totalRevenue
            ]
        ]);
    }

    // Export to CSV
    private function exportToCSV($data)
    {
       $filename = 'serve-data-' . date('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fwrite($file, "\xEF\xBB\xBF");

            // Headers
            fputcsv($file, [
                'Serve ID', 'Customer Name', 'Customer ID', 'Customer Phone',
                'Order ID', 'Order Total', 'Serve Type', 'Base Price',
                'Upgrade Enabled', 'Upgrade Price', 'Total Price', 'Status',
                'QVSE CID', 'Notes', 'Upgrade Notes', 'Created Date'
            ]);

            // Rows
            foreach ($data as $serve) {
                $totalPrice = $serve->serve ? $serve->serve->fee : 0;
                if ($serve->upgrade_pce_enabled) {
                    $totalPrice += ($serve->upgrade_price ?: 69.90);
                }

                fputcsv($file, [
                    $serve->serve_id,
                    $serve->customer->full_name ?? '',
                    $serve->customer->customer_id ?? '',
                    $serve->customer->phone ?? '',
                    $serve->order->order_id ?? '',
                    $serve->order->total ?? 0,
                    $serve->serve->name ?? '',
                    $serve->serve->fee ?? 0,
                    $serve->upgrade_pce_enabled ? 'Yes' : 'No',
                    $serve->upgrade_price ?? '',
                    $totalPrice,
                    $serve->start_serve_enabled ? 'Started' : 'Not Started',
                    $serve->qvse_cid,
                    $serve->notes,
                    $serve->upgrade_pce_notes,
                    $serve->created_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
