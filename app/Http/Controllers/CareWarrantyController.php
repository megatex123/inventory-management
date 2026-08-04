<?php

namespace App\Http\Controllers;

use App\Models\CareWarranty;
use App\Models\CareData;
use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class CareWarrantyController extends Controller
{
    use FiltersSortsAndPaginates;

    public function index(Request $request)
    {
        $query = $this->buildCareWarrantyQuery($request);

        $this->resolveSortAndApply($query, $request, ['care_warranty_id', 'care_invoice_id', 'product_id', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 10);
        $careWarranties = $query->paginate($perPage);

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
     * All care warranties matching the same filters as index(), unpaginated,
     * for the export feature. Added in the List Page Standardization
     * initiative (Batch 18) -- this route previously did not exist, so
     * "Export All" and "Export Filtered" (Excel format) silently 404'd via
     * the {id} wildcard treating "all" as an id.
     */
    public function all(Request $request)
    {
        $query = $this->buildCareWarrantyQuery($request);
        $query->orderBy('created_at', 'desc');

        $items = $query->get()->map(function ($item) {
            return $this->formatCareWarantyItem($item);
        });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Shared filter-building logic for index() and all() -- extracted
     * during Batch 18 of the List Page Standardization initiative so the
     * two endpoints' filter semantics cannot drift apart. Sorting and
     * pagination are NOT applied here; each caller adds its own.
     */
    private function buildCareWarrantyQuery(Request $request)
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

        return $query;
    }

    /**
     * Store a newly created care warranty.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
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

            $data['care_warranty_id'] = \App\Support\BusinessId::next('care_warranty', 'care_warranty_id', 'CARE-CLM-', 6);

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

}
