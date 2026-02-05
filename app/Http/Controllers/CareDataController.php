<?php

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

class CareDataController extends Controller
{
    // Get all care data with filters
    public function index(Request $request)
    {
        try {
            $query = CareData::with(['customer', 'order', 'care'])
                ->select('care_data.*');

            // Search functionality
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('care_id', 'LIKE', "%{$search}%")
                      ->orWhere('total_part', 'LIKE', "%{$search}%")
                      ->orWhere('price', 'LIKE', "%{$search}%")
                      ->orWhereHas('customer', function ($q2) use ($search) {
                          $q2->where('name', 'LIKE', "%{$search}%")
                             ->orWhere('email', 'LIKE', "%{$search}%")
                             ->orWhere('customer_id', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('order', function ($q2) use ($search) {
                          $q2->where('order_number', 'LIKE', "%{$search}%")
                             ->orWhere('order_id', 'LIKE', "%{$search}%");
                      });
                });
            }

            // Filter by update_membership
            if ($request->has('update_membership') && $request->update_membership != '') {
                $query->where('update_membership', $request->update_membership);
            }

            // Filter by customer
            if ($request->has('customer_id') && $request->customer_id != '') {
                $query->where('customer_id', $request->customer_id);
            }

            // Filter by order
            if ($request->has('order_id') && $request->order_id != '') {
                $query->where('order_id', $request->order_id);
            }

            // Filter by care type
            if ($request->has('lkp_care_id') && $request->lkp_care_id != '') {
                $query->where('lkp_care_id', $request->lkp_care_id);
            }

            // Date range filter
            if ($request->has('date_from') && $request->date_from != '') {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to != '') {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Start date and end date for range (backward compatibility)
            if ($request->has('start_date') && $request->has('end_date') &&
                $request->start_date != '' && $request->end_date != '') {
                $query->whereBetween('created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
            }

            // Order by
            $orderBy = $request->get('order_by', 'created_at');
            $orderDirection = $request->get('order_direction', 'desc');
            $query->orderBy($orderBy, $orderDirection);

            // Handle export
            if ($request->has('export') && $request->export === 'csv') {
                return $this->exportToCSV($query->get());
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
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
                    'last_page' => ceil($total / $perPage),
                    'from' => ($currentPage - 1) * $perPage + 1,
                    'to' => min($currentPage * $perPage, $total)
                ],
                'message' => 'Care data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve care data: ' . $e->getMessage()
            ], 500);
        }
    }

    // Search endpoint (similar to index with search)
    public function search(Request $request)
    {
        return $this->index($request);
    }

    // Get care data by customer
    public function byCustomer($customerId)
    {
        try {
            $careData = CareData::with(['customer', 'order', 'care'])
                ->where('customer_id', $customerId)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();

            $customer = Customers::find($customerId);

            return response()->json([
                'success' => true,
                'data' => $careData,
                'customer' => $customer,
                'message' => 'Care data for customer retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve customer care data: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get care data by order
    public function byOrder($orderId)
    {
        try {
            $careData = CareData::with(['customer', 'order', 'care'])
                ->where('order_id', $orderId)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();

            $order = Order::with(['customer'])->find($orderId);

            return response()->json([
                'success' => true,
                'data' => $careData,
                'order' => $order,
                'message' => 'Care data for order retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order care data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $careData = CareData::with(['customer', 'order', 'care'])
                ->whereNull('deleted_at')
                ->find($id);

            if (!$careData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care data not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $careData,
                'message' => 'Care data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve care data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $careData = CareData::with(['customer', 'order', 'care'])
                ->whereNull('deleted_at')
                ->find($id);

            if (!$careData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care data not found'
                ], 404);
            }

            // Get related data for dropdowns
            $customers = Customers::whereNull('deleted_at')
                ->select('id', 'name', 'email', 'customer_id')
                ->get();

            $orders = Order::whereNull('deleted_at')
                ->select('id', 'order_number', 'total')
                ->get();

            $cares = Care::whereNull('deleted_at')
                ->select('id', 'name', 'code')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'care_data' => $careData,
                    'customers' => $customers,
                    'orders' => $orders,
                    'cares' => $cares
                ],
                'message' => 'Care data edit form data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve edit data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
                'order_id' => 'required|exists:orders,id',
                'lkp_care_id' => 'required|exists:cares,id',
                'total_part' => 'nullable|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'update_membership' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }

            DB::beginTransaction();

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
                'update_membership' => $request->update_membership ?? false
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $careData->load(['customer', 'order', 'care']),
                'message' => 'Care data created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create care data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $careData = CareData::whereNull('deleted_at')->find($id);

            if (!$careData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care data not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'customer_id' => 'sometimes|required|exists:customers,id',
                'order_id' => 'sometimes|required|exists:orders,id',
                'lkp_care_id' => 'sometimes|required|exists:cares,id',
                'total_part' => 'nullable|numeric|min:0',
                'price' => 'sometimes|required|numeric|min:0',
                'update_membership' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }

            DB::beginTransaction();

            // If care type changes, we might need to regenerate care_id
            if ($request->has('lkp_care_id') && $request->lkp_care_id != $careData->lkp_care_id) {
                $careType = Care::find($request->lkp_care_id);
                $careTypeCode = $careType ? strtoupper(substr($careType->code, 0, 3)) : 'VIS';

                // Find next sequence for new care type
                $lastCare = CareData::where('lkp_care_id', $request->lkp_care_id)
                    ->orderBy('id', 'desc')
                    ->first();

                $sequence = $lastCare ?
                    intval(substr($lastCare->care_id, -4)) + 1 : 1;
                $sequenceNumber = str_pad($sequence, 4, '0', STR_PAD_LEFT);

                $monthYear = date('my');
                $newCareId = "{$careTypeCode}-{$monthYear}-{$sequenceNumber}";

                // Ensure uniqueness
                while (CareData::where('care_id', $newCareId)->exists()) {
                    $sequence++;
                    $sequenceNumber = str_pad($sequence, 4, '0', STR_PAD_LEFT);
                    $newCareId = "{$careTypeCode}-{$monthYear}-{$sequenceNumber}";
                }

                $careData->care_id = $newCareId;
            }

            $careData->update([
                'customer_id' => $request->customer_id ?? $careData->customer_id,
                'order_id' => $request->order_id ?? $careData->order_id,
                'lkp_care_id' => $request->lkp_care_id ?? $careData->lkp_care_id,
                'total_part' => $request->total_part ?? $careData->total_part,
                'price' => $request->price ?? $careData->price,
                'update_membership' => $request->has('update_membership')
                    ? $request->update_membership
                    : $careData->update_membership
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $careData->fresh()->load(['customer', 'order', 'care']),
                'message' => 'Care data updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update care data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $careData = CareData::whereNull('deleted_at')->find($id);

            if (!$careData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care data not found'
                ], 404);
            }

            DB::beginTransaction();

            // Soft delete
            $careData->deleted_at = now();
            $careData->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Care data deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete care data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function statistics(Request $request)
    {
        try {
            $query = CareData::whereNull('deleted_at');

            // Date range filter for statistics
            if ($request->has('start_date') && $request->has('end_date') &&
                $request->start_date != '' && $request->end_date != '') {
                $query->whereBetween('created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
            }

            // Date from/to (alternative)
            if ($request->has('date_from') && $request->date_from != '') {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to != '') {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // General statistics
            $totalCareData = $query->count();
            $totalPrice = $query->sum(DB::raw('CAST(price AS DECIMAL(10,2))'));
            $totalPart = $query->sum(DB::raw('CAST(total_part AS DECIMAL(10,2))'));

            // Group by update_membership
            $membershipStats = $query->select('update_membership', DB::raw('COUNT(*) as count'))
                ->groupBy('update_membership')
                ->get()
                ->pluck('count', 'update_membership');

            // Monthly statistics
            $monthlyStats = CareData::whereNull('deleted_at')
                ->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CAST(price AS DECIMAL(10,2))) as total_price'),
                    DB::raw('SUM(CAST(total_part AS DECIMAL(10,2))) as total_part')
                )
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->take(12)
                ->get();

            // Care type statistics
            $careTypeStats = CareData::whereNull('care_data.deleted_at')
                ->join('cares', 'care_data.lkp_care_id', '=', 'cares.id')
                ->select(
                    'cares.name as care_name',
                    'cares.code as care_code',
                    DB::raw('COUNT(care_data.id) as count'),
                    DB::raw('SUM(CAST(care_data.price AS DECIMAL(10,2))) as total_price'),
                    DB::raw('SUM(CAST(care_data.total_part AS DECIMAL(10,2))) as total_part')
                )
                ->groupBy('cares.name', 'cares.code')
                ->orderBy('count', 'desc')
                ->get();

            // Recent activities
            $recentActivities = CareData::with(['customer', 'order', 'care'])
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            $statistics = [
                'total_care_data' => $totalCareData,
                'total_price' => (float) $totalPrice,
                'total_part' => (float) $totalPart,
                'average_price' => $totalCareData > 0 ? (float) $totalPrice / $totalCareData : 0,
                'average_part' => $totalCareData > 0 ? (float) $totalPart / $totalCareData : 0,
                'membership_stats' => [
                    'with_membership' => $membershipStats->get(1, 0),
                    'without_membership' => $membershipStats->get(0, 0)
                ],
                'monthly_stats' => $monthlyStats,
                'care_type_stats' => $careTypeStats,
                'recent_activities' => $recentActivities
            ];

            // Check if export to CSV is requested
            if ($request->has('export') && $request->export === 'csv') {
                return $this->exportToCSV($statistics);
            }

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    private function exportToCSV($data)
    {
        try {
            $filename = 'care_data_statistics_' . date('Y-m-d_H-i-s') . '.csv';

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fwrite($output, "\xEF\xBB\xBF");

            // Overall Statistics
            fputcsv($output, ['Overall Statistics']);
            fputcsv($output, ['Total Care Data', $data['total_care_data']]);
            fputcsv($output, ['Total Price', 'RM' . number_format($data['total_price'], 2)]);
            fputcsv($output, ['Total Parts Value', 'RM' . number_format($data['total_part'], 2)]);
            fputcsv($output, ['Average Price', 'RM' . number_format($data['average_price'], 2)]);
            fputcsv($output, ['Average Parts Value', 'RM' . number_format($data['average_part'], 2)]);
            fputcsv($output, ['']);

            // Membership Statistics
            fputcsv($output, ['Membership Statistics']);
            fputcsv($output, ['With Membership Update', $data['membership_stats']['with_membership']]);
            fputcsv($output, ['Without Membership Update', $data['membership_stats']['without_membership']]);
            fputcsv($output, ['']);

            // Monthly Statistics
            fputcsv($output, ['Monthly Statistics']);
            fputcsv($output, ['Year', 'Month', 'Total Records', 'Total Price', 'Total Parts Value']);
            foreach ($data['monthly_stats'] as $monthly) {
                fputcsv($output, [
                    $monthly->year,
                    $monthly->month,
                    $monthly->total,
                    'RM' . number_format($monthly->total_price, 2),
                    'RM' . number_format($monthly->total_part, 2)
                ]);
            }
            fputcsv($output, ['']);

            // Care Type Statistics
            fputcsv($output, ['Care Type Statistics']);
            fputcsv($output, ['Care Type', 'Code', 'Count', 'Total Price', 'Total Parts Value']);
            foreach ($data['care_type_stats'] as $careType) {
                fputcsv($output, [
                    $careType->care_name,
                    $careType->care_code,
                    $careType->count,
                    'RM' . number_format($careType->total_price, 2),
                    'RM' . number_format($careType->total_part, 2)
                ]);
            }

            fclose($output);
            exit;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export CSV: ' . $e->getMessage()
            ], 500);
        }
    }

    // Additional helper methods
    public function restore($id)
    {
        try {
            $careData = CareData::withTrashed()->find($id);

            if (!$careData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care data not found'
                ], 404);
            }

            if (!$careData->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care data is not deleted'
                ], 400);
            }

            DB::beginTransaction();
            $careData->deleted_at = null;
            $careData->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $careData->load(['customer', 'order', 'care']),
                'message' => 'Care data restored successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore care data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function trashed(Request $request)
    {
        try {
            $query = CareData::onlyTrashed()->with(['customer', 'order', 'care']);

            // Search functionality
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('care_id', 'LIKE', "%{$search}%")
                      ->orWhere('total_part', 'LIKE', "%{$search}%")
                      ->orWhere('price', 'LIKE', "%{$search}%")
                      ->orWhereHas('customer', function ($q2) use ($search) {
                          $q2->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
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
                    'last_page' => ceil($total / $perPage),
                    'from' => ($currentPage - 1) * $perPage + 1,
                    'to' => min($currentPage * $perPage, $total)
                ],
                'message' => 'Trashed care data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve trashed care data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function forceDelete($id)
    {
        try {
            $careData = CareData::withTrashed()->find($id);

            if (!$careData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care data not found'
                ], 404);
            }

            DB::beginTransaction();
            $careData->forceDelete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Care data permanently deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete care data: ' . $e->getMessage()
            ], 500);
        }
    }
}
