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
use Illuminate\Support\Facades\Storage;

class CareDataController extends Controller
{
    // Get all care data with filters
    public function index(Request $request)
    {
        try {
            $query = CareData::with(['customer', 'order', 'care', 'orderItems', 'directOrderDetails'])
                ->select('care_data.*')
                ->whereNull('care_data.deleted_at');

            // Search functionality
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('care_data.care_id', 'LIKE', "%{$search}%")
                      ->orWhere('care_data.total_part', 'LIKE', "%{$search}%")
                      ->orWhere('care_data.price', 'LIKE', "%{$search}%")
                      ->orWhereHas('customer', function ($q2) use ($search) {
                          $q2->where('full_name', 'LIKE', "%{$search}%")
                             ->orWhere('email', 'LIKE', "%{$search}%")
                             ->orWhere('customer_id', 'LIKE', "%{$search}%")
                             ->orWhere('phone', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('order', function ($q2) use ($search) {
                          $q2->where('invoice_id', 'LIKE', "%{$search}%")
                             ->orWhere('order_id', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('care', function ($q2) use ($search) {
                          $q2->where('name', 'LIKE', "%{$search}%")
                             ->orWhere('code', 'LIKE', "%{$search}%");
                      });
                });
            }

            // Filter by membership status — auto-derived from remaining QuiviCare
            // coverage time (order date + care tier period), not a stored flag.
            // See CareData::getMembershipActiveAttribute().
            if ($request->has('membership_status') && $request->membership_status != '') {
                $query->leftJoin('order', 'care_data.order_id', '=', 'order.id')
                    ->join('care', 'care_data.lkp_care_id', '=', 'care.id')
                    ->whereRaw(
                        'DATE_ADD(COALESCE(order.order_date, care_data.created_at), INTERVAL CAST(SUBSTRING_INDEX(care.period, " ", 1) AS UNSIGNED) YEAR) ' .
                        ($request->membership_status === 'active' ? '>= NOW()' : '< NOW()')
                    );
            }

            // Filter by customer
            if ($request->has('customer_id') && $request->customer_id != '') {
                $query->where('care_data.customer_id', $request->customer_id);
            }

            // Filter by order
            if ($request->has('order_id') && $request->order_id != '') {
                $query->where('care_data.order_id', $request->order_id);
            }

            // Filter by care type
            if ($request->has('lkp_care_id') && $request->lkp_care_id != '') {
                $query->where('care_data.lkp_care_id', $request->lkp_care_id);
            }

            // Filter by status
            if ($request->has('status') && $request->status != '') {
                $query->where('care_data.status', $request->status);
            }

            // Date range filter for created_at
            if ($request->has('created_from') && $request->created_from != '') {
                $query->whereDate('care_data.created_at', '>=', $request->created_from);
            }

            if ($request->has('created_to') && $request->created_to != '') {
                $query->whereDate('care_data.created_at', '<=', $request->created_to);
            }

            // Date range filter for appointment_date
            if ($request->has('appointment_from') && $request->appointment_from != '') {
                $query->whereDate('care_data.appointment_date', '>=', $request->appointment_from);
            }

            if ($request->has('appointment_to') && $request->appointment_to != '') {
                $query->whereDate('care_data.appointment_date', '<=', $request->appointment_to);
            }

            // Start date and end date for range (backward compatibility)
            if ($request->has('start_date') && $request->has('end_date') &&
                $request->start_date != '' && $request->end_date != '') {
                $query->whereBetween('care_data.created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
            }

            // Order by with table prefix
            $orderBy = $request->get('order_by', 'care_data.created_at');
            $orderDirection = $request->get('order_direction', 'desc');

            // Ensure order by is safe
            $allowedOrderColumns = ['care_data.created_at', 'care_data.updated_at', 'care_data.care_id',
                                   'care_data.price', 'care_data.total_part', 'care_data.appointment_date'];
            if (!in_array($orderBy, $allowedOrderColumns)) {
                $orderBy = 'care_data.created_at';
            }

            $query->orderBy($orderBy, $orderDirection);

            // Handle export
            if ($request->has('export') && $request->export === 'csv') {
                $careData = $query->get();
                return $this->exportToCSV($careData);
            }

            if ($request->has('export') && $request->export === 'pdf') {
                $careData = $query->get();
                return $this->exportToPDF($careData);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $currentPage = $request->get('page', 1);

            $total = $query->count();
            $results = $query->paginate($perPage);

            // Get summary statistics
            $summary = [
                'total_price' => $query->sum(DB::raw('CAST(care_data.price AS DECIMAL(10,2))')),
                'total_part' => $query->sum(DB::raw('CAST(care_data.total_part AS DECIMAL(10,2))')),
                'total_count' => $total
            ];

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'meta' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem()
                ],
                'summary' => $summary,
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
        $query = CareData::query()
            ->with(['customer' => function($q) {
                $q->select('id', 'full_name as name', 'email', 'phone', 'customer_id');
            }])
            ->with(['order' => function($q) {
                $q->select('id', 'invoice_id', 'order_id');
            }])
            ->with(['care' => function($q) {
                $q->select('id', 'name', 'code');
            }]);

        // Apply search filter
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;

            // If searching by exact ID format (like VIS10N-2712-0001), try exact match first
            if (preg_match('/^[A-Z0-9]+-[0-9]+-[0-9]+$/', $search)) {
                $query->where('care_data.care_id', $search);
            } else {
                // For general searches, use LIKE with proper indexing
                $query->where(function ($q) use ($search) {
                    $q->where('care_data.care_id', 'LIKE', "%{$search}%")
                      ->orWhere('care_data.total_part', 'LIKE', "%{$search}%")
                      ->orWhere('care_data.price', 'LIKE', "%{$search}%")

                      // Search in related customer
                      ->orWhereHas('customer', function ($q2) use ($search) {
                          $q2->where('full_name', 'LIKE', "%{$search}%")
                             ->orWhere('email', 'LIKE', "%{$search}%")
                             ->orWhere('customer_id', 'LIKE', "%{$search}%")
                             ->orWhere('phone', 'LIKE', "%{$search}%");
                      })

                      // Search in related order (with invoice_id)
                      ->orWhereHas('order', function ($q2) use ($search) {
                          $q2->where('invoice_id', 'LIKE', "%{$search}%")
                             ->orWhere('order_id', 'LIKE', "%{$search}%");
                      })

                      // Search in related care
                      ->orWhereHas('care', function ($q2) use ($search) {
                          $q2->where('name', 'LIKE', "%{$search}%")
                             ->orWhere('code', 'LIKE', "%{$search}%");
                      });
                });
            }
        }

        // Apply fields filter if provided
        if ($request->has('fields')) {
            // This would require more complex logic to select specific fields
            // For now, we'll just return all fields
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $results = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage()
            ]
        ]);
    }

    // Get care data by customer
    public function byCustomer($customerId)
    {
        try {
            $customer = Customers::find($customerId);
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            $careData = CareData::with(['customer', 'order', 'care'])
                ->where('customer_id', $customerId)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate statistics for this customer
            $customerStats = [
                'total_care_records' => $careData->count(),
                'total_price' => $careData->sum('price'),
                'total_part' => $careData->sum('total_part'),
                'last_care_date' => $careData->max('created_at'),
                'care_types' => $careData->groupBy('lkp_care_id')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'total_price' => $group->sum('price')
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $careData,
                'customer' => $customer,
                'statistics' => $customerStats,
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
            $order = Order::with(['customer'])->find($orderId);
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $careData = CareData::with(['customer', 'order', 'care'])
                ->where('order_id', $orderId)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $careData,
                'order' => $order,
                'statistics' => [
                    'total_care_records' => $careData->count(),
                    'total_price' => $careData->sum('price'),
                    'total_part' => $careData->sum('total_part')
                ],
                'message' => 'Care data for order retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order care data: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get upcoming appointments
    public function upcomingAppointments(Request $request)
    {
        try {
            $query = CareData::with(['customer', 'order', 'care'])
                ->whereNull('deleted_at')
                ->whereNotNull('appointment_date')
                ->where('appointment_date', '>=', now())
                ->whereIn('status', ['scheduled', 'confirmed', 'pending']);

            // Filter by date range
            if ($request->has('start_date') && $request->start_date != '') {
                $query->whereDate('appointment_date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date != '') {
                $query->whereDate('appointment_date', '<=', $request->end_date);
            }

            // Filter by customer
            if ($request->has('customer_id') && $request->customer_id != '') {
                $query->where('customer_id', $request->customer_id);
            }

            // Order by appointment date
            $query->orderBy('appointment_date', 'asc')
                  ->orderBy('appointment_time', 'asc');

            $perPage = $request->get('per_page', 20);
            $appointments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $appointments->items(),
                'meta' => [
                    'total' => $appointments->total(),
                    'per_page' => $appointments->perPage(),
                    'current_page' => $appointments->currentPage(),
                    'last_page' => $appointments->lastPage()
                ],
                'message' => 'Upcoming appointments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming appointments: ' . $e->getMessage()
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
                ->select('id', 'name', 'email', 'customer_id', 'phone')
                ->orderBy('name')
                ->get();

            $orders = Order::whereNull('deleted_at')
                ->select('id', 'order_number', 'total', 'customer_id')
                ->orderBy('order_number', 'desc')
                ->get();

            $cares = Care::whereNull('deleted_at')
                ->select('id', 'name', 'code', 'description', 'price_range')
                ->orderBy('name')
                ->get();

            // Get status options
            $statusOptions = [
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'scheduled', 'label' => 'Scheduled'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
                ['value' => 'no_show', 'label' => 'No Show']
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'care_data' => $careData,
                    'customers' => $customers,
                    'orders' => $orders,
                    'cares' => $cares,
                    'status_options' => $statusOptions
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
                'order_id' => 'nullable|exists:orders,id',
                'lkp_care_id' => 'required|exists:cares,id',
                'total_part' => 'nullable|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'status' => 'nullable|in:pending,scheduled,in_progress,completed,cancelled,no_show',
                'appointment_date' => 'nullable|date',
                'appointment_time' => 'nullable|date_format:H:i',
                'notes' => 'nullable|string|max:1000',
                'service_duration' => 'nullable|integer|min:1',
                'technician_notes' => 'nullable|string|max:500',
                'customer_feedback' => 'nullable|string|max:500',
                'rating' => 'nullable|integer|min:1|max:5'
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
                'status' => $request->status ?? 'pending',
                'appointment_date' => $request->appointment_date,
                'appointment_time' => $request->appointment_time,
                'notes' => $request->notes,
                'service_duration' => $request->service_duration,
                'technician_notes' => $request->technician_notes,
                'customer_feedback' => $request->customer_feedback,
                'rating' => $request->rating
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
            $careData = CareData::whereNull('deleted_at')
                ->find($id);

            if (!$careData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care data not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'customer_id' => 'sometimes|required|exists:customers,id',
                // 'order_id' => 'nullable|exists:orders,id',
                // 'lkp_care_id' => 'sometimes|required|exists:cares,id',
                'total_part' => 'nullable|numeric|min:0',
                'price' => 'sometimes|required|numeric|min:0',
                'status' => 'nullable|in:pending,scheduled,in_progress,completed,cancelled,no_show',
                'appointment_date' => 'nullable|date',
                'appointment_time' => 'nullable|date_format:H:i',
                'notes' => 'nullable|string|max:1000',
                'service_duration' => 'nullable|integer|min:1',
                'technician_notes' => 'nullable|string|max:500',
                'customer_feedback' => 'nullable|string|max:500',
                'rating' => 'nullable|integer|min:1|max:5'
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

            // Update main fields
            $updateData = [
                'customer_id' => $request->customer_id ?? $careData->customer_id,
                'order_id' => $request->has('order_id') ? $request->order_id : $careData->order_id,
                'lkp_care_id' => $request->lkp_care_id ?? $careData->lkp_care_id,
                'total_part' => $request->total_part ?? $careData->total_part,
                'price' => $request->price ?? $careData->price,
                'status' => $request->status ?? $careData->status,
                'appointment_date' => $request->appointment_date ?? $careData->appointment_date,
                'appointment_time' => $request->appointment_time ?? $careData->appointment_time,
                'notes' => $request->notes ?? $careData->notes,
                'service_duration' => $request->service_duration ?? $careData->service_duration,
                'technician_notes' => $request->technician_notes ?? $careData->technician_notes,
                'customer_feedback' => $request->customer_feedback ?? $careData->customer_feedback,
                'rating' => $request->rating ?? $careData->rating
            ];

            $careData->update($updateData);
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

    public function updateStatus(Request $request, $id)
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
                'status' => 'required|in:pending,scheduled,in_progress,completed,cancelled,no_show',
                'status_notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }

            DB::beginTransaction();

            $oldStatus = $careData->status;
            $careData->status = $request->status;

            // Add status history
            $careData->statusHistory()->create([
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'notes' => $request->status_notes,
                'changed_by' => auth()->id() ?? null
            ]);

            // If completing, set completion date
            if ($request->status === 'completed' && !$careData->completed_at) {
                $careData->completed_at = now();
            }

            // If cancelled, set cancellation date
            if ($request->status === 'cancelled' && !$careData->cancelled_at) {
                $careData->cancelled_at = now();
                $careData->cancellation_reason = $request->cancellation_reason ?? null;
            }

            $careData->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $careData->fresh()->load(['customer', 'order', 'care']),
                'message' => 'Status updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
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
            $careData->deleted_by = auth()->id() ?? null;
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

            // Filter by customer
            if ($request->has('customer_id') && $request->customer_id != '') {
                $query->where('customer_id', $request->customer_id);
            }

            // Filter by care type
            if ($request->has('lkp_care_id') && $request->lkp_care_id != '') {
                $query->where('lkp_care_id', $request->lkp_care_id);
            }

            // Filter by status
            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }

            // General statistics
            $totalCareData = $query->count();
            $totalPrice = $query->sum(DB::raw('CAST(price AS DECIMAL(10,2))'));
            $totalPart = $query->sum(DB::raw('CAST(total_part AS DECIMAL(10,2))'));

            // Membership is derived from remaining QuiviCare coverage time (order
            // date + care tier period), not a stored flag — see CareData::getMembershipActiveAttribute().
            $membershipRecords = (clone $query)->with(['order', 'care'])->get();
            $withMembership = $membershipRecords->filter->membership_active->count();
            $withoutMembership = $membershipRecords->count() - $withMembership;

            // Status statistics
            $statusStats = CareData::whereNull('deleted_at')
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status');

            // Monthly statistics
            $monthlyStats = CareData::whereNull('deleted_at')
                ->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CAST(price AS DECIMAL(10,2))) as total_price'),
                    DB::raw('SUM(CAST(total_part AS DECIMAL(10,2))) as total_part'),
                    DB::raw('AVG(CAST(price AS DECIMAL(10,2))) as avg_price')
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
                    'cares.id as care_id',
                    'cares.name as care_name',
                    'cares.code as care_code',
                    DB::raw('COUNT(care_data.id) as count'),
                    DB::raw('SUM(CAST(care_data.price AS DECIMAL(10,2))) as total_price'),
                    DB::raw('SUM(CAST(care_data.total_part AS DECIMAL(10,2))) as total_part'),
                    DB::raw('AVG(CAST(care_data.price AS DECIMAL(10,2))) as avg_price')
                )
                ->groupBy('cares.id', 'cares.name', 'cares.code')
                ->orderBy('count', 'desc')
                ->get();

            // Top customers by care count
            $topCustomers = CareData::whereNull('care_data.deleted_at')
                ->join('customers', 'care_data.customer_id', '=', 'customers.id')
                ->select(
                    'customers.id',
                    'customers.name',
                    'customers.customer_id as customer_code',
                    DB::raw('COUNT(care_data.id) as care_count'),
                    DB::raw('SUM(CAST(care_data.price AS DECIMAL(10,2))) as total_spent')
                )
                ->groupBy('customers.id', 'customers.name', 'customers.customer_id')
                ->orderBy('care_count', 'desc')
                ->take(10)
                ->get();

            // Recent activities
            $recentActivities = CareData::with(['customer', 'order', 'care'])
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            // Appointment statistics
            $appointmentStats = [
                'upcoming' => CareData::whereNull('deleted_at')
                    ->where('status', 'scheduled')
                    ->where('appointment_date', '>=', now())
                    ->count(),
                'today' => CareData::whereNull('deleted_at')
                    ->where('status', 'scheduled')
                    ->whereDate('appointment_date', now()->toDateString())
                    ->count(),
                'overdue' => CareData::whereNull('deleted_at')
                    ->where('status', 'scheduled')
                    ->where('appointment_date', '<', now())
                    ->count(),
                'completed' => CareData::whereNull('deleted_at')
                    ->where('status', 'completed')
                    ->count(),
                'cancelled' => CareData::whereNull('deleted_at')
                    ->where('status', 'cancelled')
                    ->count()
            ];

            $statistics = [
                'total_care_data' => $totalCareData,
                'total_price' => (float) $totalPrice,
                'total_part' => (float) $totalPart,
                'average_price' => $totalCareData > 0 ? (float) $totalPrice / $totalCareData : 0,
                'average_part' => $totalCareData > 0 ? (float) $totalPart / $totalCareData : 0,
                'membership_stats' => [
                    'with_membership' => $withMembership,
                    'without_membership' => $withoutMembership
                ],
                'status_stats' => $statusStats,
                'monthly_stats' => $monthlyStats,
                'care_type_stats' => $careTypeStats,
                'top_customers' => $topCustomers,
                'appointment_stats' => $appointmentStats,
                'recent_activities' => $recentActivities
            ];

            // Check if export to CSV is requested
            if ($request->has('export') && $request->export === 'csv') {
                return $this->exportStatisticsToCSV($statistics);
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

    private function exportToCSV($careData)
    {
        try {
            $filename = 'care_data_export_' . date('Y-m-d_H-i-s') . '.csv';

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fwrite($output, "\xEF\xBB\xBF");

            // Header row
            fputcsv($output, [
                'Care ID', 'Customer Name', 'Customer Email', 'Order Number',
                'Care Type', 'Price (RM)', 'Parts Value (RM)', 'Total Value (RM)',
                'Status', 'Appointment Date', 'Membership Active', 'Notes',
                'Created Date', 'Completed Date'
            ]);

            // Data rows
            foreach ($careData as $data) {
                fputcsv($output, [
                    $data->care_id,
                    $data->customer->name ?? '',
                    $data->customer->email ?? '',
                    $data->order->order_number ?? '',
                    $data->care->name ?? '',
                    number_format($data->price, 2),
                    number_format($data->total_part, 2),
                    number_format($data->price + $data->total_part, 2),
                    ucfirst(str_replace('_', ' ', $data->status)),
                    $data->appointment_date ? Carbon::parse($data->appointment_date)->format('Y-m-d') : '',
                    $data->membership_active ? 'Yes' : 'No',
                    substr($data->notes ?? '', 0, 100),
                    $data->created_at->format('Y-m-d H:i:s'),
                    $data->completed_at ? Carbon::parse($data->completed_at)->format('Y-m-d H:i:s') : ''
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

    private function exportStatisticsToCSV($statistics)
    {
        try {
            $filename = 'care_statistics_' . date('Y-m-d_H-i-s') . '.csv';

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fwrite($output, "\xEF\xBB\xBF");

            // Overall Statistics
            fputcsv($output, ['OVERALL STATISTICS']);
            fputcsv($output, ['Total Care Records', $statistics['total_care_data']]);
            fputcsv($output, ['Total Price', 'RM ' . number_format($statistics['total_price'], 2)]);
            fputcsv($output, ['Total Parts Value', 'RM ' . number_format($statistics['total_part'], 2)]);
            fputcsv($output, ['Average Price per Record', 'RM ' . number_format($statistics['average_price'], 2)]);
            fputcsv($output, ['Average Parts Value', 'RM ' . number_format($statistics['average_part'], 2)]);
            fputcsv($output, []);

            // Membership Statistics
            fputcsv($output, ['MEMBERSHIP STATISTICS']);
            fputcsv($output, ['With Membership Update', $statistics['membership_stats']['with_membership']]);
            fputcsv($output, ['Without Membership Update', $statistics['membership_stats']['without_membership']]);
            fputcsv($output, []);

            // Status Statistics
            fputcsv($output, ['STATUS DISTRIBUTION']);
            fputcsv($output, ['Status', 'Count']);
            foreach ($statistics['status_stats'] as $status => $count) {
                fputcsv($output, [ucfirst(str_replace('_', ' ', $status)), $count]);
            }
            fputcsv($output, []);

            // Appointment Statistics
            fputcsv($output, ['APPOINTMENT STATISTICS']);
            fputcsv($output, ['Upcoming Appointments', $statistics['appointment_stats']['upcoming']]);
            fputcsv($output, ['Appointments Today', $statistics['appointment_stats']['today']]);
            fputcsv($output, ['Overdue Appointments', $statistics['appointment_stats']['overdue']]);
            fputcsv($output, ['Completed Appointments', $statistics['appointment_stats']['completed']]);
            fputcsv($output, ['Cancelled Appointments', $statistics['appointment_stats']['cancelled']]);
            fputcsv($output, []);

            // Monthly Statistics
            fputcsv($output, ['MONTHLY STATISTICS (Last 12 Months)']);
            fputcsv($output, ['Year', 'Month', 'Total Records', 'Total Price (RM)', 'Total Parts (RM)', 'Average Price (RM)']);
            foreach ($statistics['monthly_stats'] as $monthly) {
                fputcsv($output, [
                    $monthly->year,
                    Carbon::create()->month($monthly->month)->format('F'),
                    $monthly->total,
                    number_format($monthly->total_price, 2),
                    number_format($monthly->total_part, 2),
                    number_format($monthly->avg_price ?? 0, 2)
                ]);
            }
            fputcsv($output, []);

            // Care Type Statistics
            fputcsv($output, ['CARE TYPE STATISTICS']);
            fputcsv($output, ['Care Type', 'Code', 'Count', 'Total Price (RM)', 'Total Parts (RM)', 'Average Price (RM)']);
            foreach ($statistics['care_type_stats'] as $careType) {
                fputcsv($output, [
                    $careType->care_name,
                    $careType->care_code,
                    $careType->count,
                    number_format($careType->total_price, 2),
                    number_format($careType->total_part, 2),
                    number_format($careType->avg_price ?? 0, 2)
                ]);
            }
            fputcsv($output, []);

            // Top Customers
            fputcsv($output, ['TOP 10 CUSTOMERS BY CARE COUNT']);
            fputcsv($output, ['Customer Name', 'Customer Code', 'Care Count', 'Total Spent (RM)']);
            foreach ($statistics['top_customers'] as $customer) {
                fputcsv($output, [
                    $customer->name,
                    $customer->customer_code,
                    $customer->care_count,
                    number_format($customer->total_spent, 2)
                ]);
            }

            fclose($output);
            exit;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export statistics CSV: ' . $e->getMessage()
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
            $careData->deleted_by = null;
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
            $query = CareData::onlyTrashed()
                ->with(['customer', 'order', 'care', 'deleter']);

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
                      });
                });
            }

            // Date range filter
            if ($request->has('deleted_from') && $request->deleted_from != '') {
                $query->whereDate('deleted_at', '>=', $request->deleted_from);
            }

            if ($request->has('deleted_to') && $request->deleted_to != '') {
                $query->whereDate('deleted_at', '<=', $request->deleted_to);
            }

            // Order by deletion date
            $query->orderBy('deleted_at', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 15);
            $results = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'meta' => [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem()
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
            $careData = CareData::withTrashed()
                ->find($id);

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

    // Bulk operations
    public function bulkDelete(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:care_data,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }

            DB::beginTransaction();

            $count = CareData::whereIn('id', $request->ids)
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => now(),
                    'deleted_by' => auth()->id()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ['deleted_count' => $count],
                'message' => "{$count} care records deleted successfully"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk delete: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkRestore(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:care_data,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }

            DB::beginTransaction();

            $count = CareData::withTrashed()
                ->whereIn('id', $request->ids)
                ->whereNotNull('deleted_at')
                ->update([
                    'deleted_at' => null,
                    'deleted_by' => null
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ['restored_count' => $count],
                'message' => "{$count} care records restored successfully"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk restore: ' . $e->getMessage()
            ], 500);
        }
    }

    // Dashboard statistics
    public function dashboardStats()
    {
        try {
            $today = now()->toDateString();
            $startOfWeek = now()->startOfWeek()->toDateString();
            $startOfMonth = now()->startOfMonth()->toDateString();
            $startOfYear = now()->startOfYear()->toDateString();

            $stats = [
                'today' => [
                    'count' => CareData::whereNull('deleted_at')
                        ->whereDate('created_at', $today)
                        ->count(),
                    'revenue' => CareData::whereNull('deleted_at')
                        ->whereDate('created_at', $today)
                        ->sum(DB::raw('CAST(price AS DECIMAL(10,2))'))
                ],
                'this_week' => [
                    'count' => CareData::whereNull('deleted_at')
                        ->whereDate('created_at', '>=', $startOfWeek)
                        ->count(),
                    'revenue' => CareData::whereNull('deleted_at')
                        ->whereDate('created_at', '>=', $startOfWeek)
                        ->sum(DB::raw('CAST(price AS DECIMAL(10,2))'))
                ],
                'this_month' => [
                    'count' => CareData::whereNull('deleted_at')
                        ->whereDate('created_at', '>=', $startOfMonth)
                        ->count(),
                    'revenue' => CareData::whereNull('deleted_at')
                        ->whereDate('created_at', '>=', $startOfMonth)
                        ->sum(DB::raw('CAST(price AS DECIMAL(10,2))'))
                ],
                'this_year' => [
                    'count' => CareData::whereNull('deleted_at')
                        ->whereDate('created_at', '>=', $startOfYear)
                        ->count(),
                    'revenue' => CareData::whereNull('deleted_at')
                        ->whereDate('created_at', '>=', $startOfYear)
                        ->sum(DB::raw('CAST(price AS DECIMAL(10,2))'))
                ],
                'total' => [
                    'count' => CareData::whereNull('deleted_at')->count(),
                    'revenue' => CareData::whereNull('deleted_at')
                        ->sum(DB::raw('CAST(price AS DECIMAL(10,2))'))
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Dashboard statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
