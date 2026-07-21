<?php

namespace App\Http\Controllers;

use App\Models\CareWarranty;
use App\Models\CareData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class CareWarrantyController extends Controller
{
    public function index(Request $request)
    {
        $query = CareWarranty::with([
            'careData.customer' => function ($q) {
                $q->select('id', 'full_name as name', 'email', 'customer_id');
            },
            'category',
            'spareCategory',
            'product',
        ]);

        if ($request->has('care_warranty_id') && !empty($request->care_warranty_id)) {
            $query->where('care_warranty_id', 'like', '%' . $request->care_warranty_id . '%');
        }

        if ($request->has('care_invoice_id') && !empty($request->care_invoice_id)) {
            $query->where('care_invoice_id', 'like', '%' . $request->care_invoice_id . '%');
        }

        if ($request->has('care_data_id') && !empty($request->care_data_id)) {
            $query->where('care_data_id', $request->care_data_id);
        }

        if ($request->has('product_id') && !empty($request->product_id)) {
            $query->where('product_id', 'like', '%' . $request->product_id . '%');
        }

        if ($request->has('warranty_status') && $request->warranty_status !== '') {
            if ($request->warranty_status === 'active') {
                $query->activeWaranty();
            } elseif ($request->warranty_status === 'expired') {
                $query->expiredWaranty();
            }
        }

        if ($request->has('reset_status') && $request->reset_status !== '') {
            $query->where('reset_status', $request->reset_status);
        }

        if ($request->has('date_start_from') && !empty($request->date_start_from)) {
            $query->where('date_start', '>=', $request->date_start_from);
        }

        if ($request->has('date_start_to') && !empty($request->date_start_to)) {
            $query->where('date_start', '<=', $request->date_start_to);
        }

        if ($request->has('loan_date_end_from') && !empty($request->loan_date_end_from)) {
            $query->where('loan_date_end', '>=', $request->loan_date_end_from);
        }

        if ($request->has('loan_date_end_to') && !empty($request->loan_date_end_to)) {
            $query->where('loan_date_end', '<=', $request->loan_date_end_to);
        }

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('care_warranty_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('care_invoice_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('product_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('i_qvca_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('spare_item_name', 'like', '%' . $searchTerm . '%');
            });
        }

        // Sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $careWarranties = $query->paginate($perPage);

        // Transform data
        $transformedData = collect($careWarranties->items())->map(function ($item) {
            return $this->formatCareWarantyItem($item);
        });

        return response()->json([
            'success' => true,
            'data' => $transformedData,
            'meta' => [
                'total' => $careWarranties->total(),
                'per_page' => $careWarranties->perPage(),
                'current_page' => $careWarranties->currentPage(),
                'last_page' => $careWarranties->lastPage(),
                'from' => $careWarranties->firstItem(),
                'to' => $careWarranties->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created care warranty.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'care_warranty_id' => 'required|string|max:255|unique:care_warranty,care_warranty_id',
                'care_data_id' => 'required|exists:care_data,id',
                'care_invoice_id' => 'required|string|max:255',
                'product_id' => 'nullable|string|max:255',
                'category_id' => 'nullable|exists:categories,id',
                'eligible_warranty' => 'nullable|boolean',
                'eligible_qvca' => 'nullable|boolean',
                'i_qvca_id' => 'nullable|string|max:255',
                'spare_item_name' => 'nullable|string|max:255',
                'spare_category_id' => 'nullable|exists:categories,id',
                'date_start' => 'nullable|date',
                'loan_date_end' => 'nullable|date|after_or_equal:date_start',
                'reset_status' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if care_data exists and get additional info if needed
            $careData = CareData::find($request->care_data_id);
            if (!$careData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care Data record not found'
                ], 404);
            }

            // Prepare data
            $data = $validator->validated();

            // Set default values for boolean fields if not provided
            $data['reset_status'] = $data['reset_status'] ?? false;

            $CareWarranty = CareWarranty::create($data);

            DB::commit();

            $formattedItem = $this->formatCareWarantyItem($CareWarranty->load(['careData', 'category', 'spareCategory', 'product']));

            return response()->json([
                'success' => true,
                'data' => $formattedItem,
                'message' => 'Care warranty created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create care warranty',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified care warranty.
     */
    public function show($id)
    {
        try {
            $CareWarranty = CareWarranty::with(['careData', 'category', 'spareCategory', 'product'])->findOrFail($id);
            $formattedItem = $this->formatCareWarantyItem($CareWarranty);

            return response()->json([
                'success' => true,
                'data' => $formattedItem
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Care warranty not found'
            ], 404);
        }
    }

    /**
     * Update the specified care warranty.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $CareWarranty = CareWarranty::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'care_warranty_id' => 'sometimes|required|string|max:255|unique:care_warranty,care_warranty_id,' . $id,
                'care_data_id' => 'sometimes|required|exists:care_data,id',
                'care_invoice_id' => 'sometimes|required|string|max:255',
                'product_id' => 'nullable|string|max:255',
                'category_id' => 'nullable|exists:categories,id',
                'eligible_warranty' => 'nullable|boolean',
                'eligible_qvca' => 'nullable|boolean',
                'i_qvca_id' => 'nullable|string|max:255',
                'spare_item_name' => 'nullable|string|max:255',
                'spare_category_id' => 'nullable|exists:categories,id',
                'date_start' => 'nullable|date',
                'loan_date_end' => 'nullable|date|after_or_equal:date_start',
                'reset_status' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $CareWarranty->update($validator->validated());

            DB::commit();

            $formattedItem = $this->formatCareWarantyItem($CareWarranty->fresh()->load(['careData', 'category', 'spareCategory', 'product']));

            return response()->json([
                'success' => true,
                'data' => $formattedItem,
                'message' => 'Care warranty updated successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Care warranty not found'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update care warranty',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified care warranty.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $CareWarranty = CareWarranty::findOrFail($id);
            $CareWarranty->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Care warranty deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Care warranty not found'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete care warranty',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get statistics for care warranties
     */
    public function statistics()
    {
        $total = CareWarranty::count();

        // Warranty status stats
        $activeWarranty = CareWarranty::activeWaranty()->count();
        $expiredWarranty = CareWarranty::expiredWaranty()->count();

        // Reset status stats
        $resetStatus = CareWarranty::where('reset_status', true)->count();

        // Items with QVCA IDs
        $withQvcaId = CareWarranty::whereNotNull('i_qvca_id')->count();

        // Items with spare parts
        $withSpareParts = CareWarranty::whereNotNull('spare_item_name')->count();

        // Calculate percentages
        $activeWarrantyPercentage = $total > 0 ? round(($activeWarranty / $total) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_records' => $total,
                'active_warranty' => $activeWarranty,
                'expired_warranty' => $expiredWarranty,
                'reset_status' => $resetStatus,
                'with_qvca_id' => $withQvcaId,
                'with_spare_parts' => $withSpareParts,
                'active_warranty_percentage' => $activeWarrantyPercentage
            ]
        ]);
    }

    /**
     * Format CareWarranty item to include related data
     */
    private function formatCareWarantyItem($CareWarranty)
    {
        if (!$CareWarranty) {
            return null;
        }

        $item = $CareWarranty->toArray();

        // Add computed attributes

        if ($CareWarranty->relationLoaded('product') && $CareWarranty->product) {
            $item['product'] = [
                'id' => $CareWarranty->product->id,
                'name' => $CareWarranty->product->product_name,
                'code' => $CareWarranty->product->product_code,
                // Add other relevant fields
            ];
        }

        $item['warranty_status'] = $CareWarranty->warranty_status;
        $item['is_warranty_active'] = $CareWarranty->isWarantyActive();

        // Add care data info if loaded
        if ($CareWarranty->relationLoaded('careData') && $CareWarranty->careData) {
            $customer = $CareWarranty->careData->customer;
            $item['care_data'] = [
                'id'              => $CareWarranty->careData->id,
                'care_invoice_id' => $CareWarranty->careData->care_invoice_id ?? null,
                'order_id'        => $CareWarranty->careData->order_id,
            ];
            $item['customer_id']   = $customer ? $customer->customer_id : null;
            $item['customer_name'] = $customer ? $customer->name : null;
        }

        // Add category info if loaded
        if ($CareWarranty->relationLoaded('category') && $CareWarranty->category) {
            $item['category'] = [
                'id' => $CareWarranty->category->id,
                'name' => $CareWarranty->category->name,
                // Add other relevant fields
            ];
        }

        // Add spare category info if loaded
        if ($CareWarranty->relationLoaded('spareCategory') && $CareWarranty->spareCategory) {
            $item['spare_category'] = [
                'id' => $CareWarranty->spareCategory->id,
                'name' => $CareWarranty->spareCategory->name,
            ];
        }

        return $item;
    }

    /**
     * Get the next available warranty ID
     */
    public function getNextId()
    {
        try {
            // Maximum number of warranty IDs (4 digits = 0001 to 9999)
            $maxNumber = 9999;
            $foundAvailableId = false;
            $attempts = 0;
            $maxAttempts = 100; // Prevent infinite loop
            $nextNumber = 1;

            // Keep trying until we find an available ID
            while (!$foundAvailableId && $attempts < $maxAttempts) {
                // Get the last warranty record ordered by ID descending
                $lastWarranty = CareWarranty::orderBy('id', 'desc')->first();

                if (!$lastWarranty || !$lastWarranty->care_warranty_id) {
                    $nextNumber = 1;
                } else {
                    // Extract the number from the last ID (format: QV-CLA-XXXX)
                    preg_match('/QV-CLA-(\d+)/', $lastWarranty->care_warranty_id, $matches);

                    if (isset($matches[1]) && is_numeric($matches[1])) {
                        $nextNumber = intval($matches[1]) + 1;
                    } else {
                        $nextNumber = 1;
                    }
                }

                // Handle overflow
                if ($nextNumber > $maxNumber) {
                    // If we've exceeded the max, we need to find a gap
                    $nextNumber = $this->findFirstAvailableWarrantyNumber();
                    if ($nextNumber === null) {
                        // No available numbers found
                        return response()->json([
                            'success' => false,
                            'message' => 'No available warranty IDs left',
                            'data' => null
                        ], 400);
                    }
                }

                // Format with leading zeros
                $warrantyId = 'QV-CLA-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

                // Check if this ID already exists (to prevent duplicates)
                $existingWarranty = CareWarranty::where('care_warranty_id', $warrantyId)->first();

                if (!$existingWarranty) {
                    $foundAvailableId = true;
                } else {
                    // If it exists, increment and try again
                    $nextNumber++;
                    $attempts++;
                }
            }

            if (!$foundAvailableId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to find an available warranty ID after multiple attempts',
                    'data' => null
                ], 400);
            }

            // Prepare the response
            return response()->json([
                'success' => true,
                'data' => [
                    'warranty_id' => $warrantyId,      // For frontend expecting underscore
                    'warrantyId' => $warrantyId,       // For frontend expecting camelCase
                    'next_number' => $nextNumber,
                    'nextNumber' => $nextNumber
                ],
                'message' => 'Warranty ID generated successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to generate warranty ID: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate warranty ID',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'data' => null
            ], 500);
        }
    }

    /**
     * Helper method to find the first available warranty number
     *
     * @return int|null
     */
    private function findFirstAvailableWarrantyNumber()
    {
        $maxNumber = 9999;

        for ($i = 1; $i <= $maxNumber; $i++) {
            $warrantyId = 'QV-CLA-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $exists = CareWarranty::where('care_warranty_id', $warrantyId)->exists();

            if (!$exists) {
                return $i;
            }
        }

        return null; // No available numbers
    }
}
