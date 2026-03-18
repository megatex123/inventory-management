<?php

namespace App\Http\Controllers;

use App\Models\ServeData;
use App\Models\Serves;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ServeDataController extends Controller
{
    public function index(Request $request)
    {
        $query = ServeData::with(['customer', 'order', 'serve'])->select('serve_data.*');

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('start_serve_enabled', 1);
            } elseif ($request->status === 'not_started') {
                $query->where('start_serve_enabled', 0);
            } elseif ($request->status === 'with_upgrade') {
                $query->where('upgrade_pce_enabled', 1);
            }
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('lkp_serve_id')) {
            $query->where('lkp_serve_id', $request->lkp_serve_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('serve_id', 'LIKE', "%{$search}%")
                ->orWhere('qvse_cid', 'LIKE', "%{$search}%")
                ->orWhereHas('customer', function($q) use ($search) {
                    $q->where('full_name', 'LIKE', "%{$search}%")
                        ->orWhere('customer_id', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('order', function($q) use ($search) {
                    $q->where('order_id', 'LIKE', "%{$search}%");
                });
            });
        }

        if ($request->filled('sort_by')) {
            $sortBy = $request->sort_by;
            switch ($sortBy) {
                case 'created_at_asc':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'customer_name_asc':
                    $query->join('customers', 'serve_data.customer_id', '=', 'customers.id')
                        ->orderBy('customers.full_name', 'asc')
                        ->select('serve_data.*');
                    break;
                case 'customer_name_desc':
                    $query->join('customers', 'serve_data.customer_id', '=', 'customers.id')
                        ->orderBy('customers.full_name', 'desc')
                        ->select('serve_data.*');
                    break;
                case 'serve_id_asc':
                    $query->orderBy('serve_id', 'asc');
                    break;
                case 'serve_id_desc':
                    $query->orderBy('serve_id', 'desc');
                    break;
                case 'created_at_desc':
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->has('export') && $request->export === 'csv') {
            return $this->exportToCSV($query->get());
        }
        $perPage = $request->get('per_page', 10);
        $currentPage = $request->get('page', 1);

        $total = $query->count();
        $results = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => $results->lastPage()
            ]
        ]);
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

            // Generate serve ID - Change from QV-SRV- to QVSE-
            $totalServes = ServeData::count();
            $nextId = $totalServes + 1;
            $serveNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
            $serveId = "QV-SERV-{$serveNumber}"; // Changed from QV-SRV-

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
            $serveData->delete();

            return response()->json([
                'success' => true,
                'message' => 'Serve data deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete serve data',
                'error' => $e->getMessage()
            ], 500);
        }
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
