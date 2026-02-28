<?php
// app/Http/Controllers/ProductWarrantyController.php

namespace App\Http\Controllers;

use App\Models\ProductWarranty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductWarrantyController extends Controller
{
    /**
     * Display a listing of product warranties.
     */
    public function index(Request $request)
    {
        $query = ProductWarranty::query()->with('product.category'); // Add with('product') to eager load the relationship
        // Apply filters from scope
        $query->filter($request);

        // Sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['id', 'product_id', 'product_code', 'product_name', 'serial_no', 'created_at', 'updated_at'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $productWarranties = $query->paginate($perPage);

        // Transform data
        $transformedData = collect($productWarranties->items())->map(function ($item) {
            return $this->formatProductWarrantyItem($item);
        });

        // Create new paginator with transformed data
        $paginator = new LengthAwarePaginator(
            $transformedData,
            $productWarranties->total(),
            $productWarranties->perPage(),
            $productWarranties->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created product warranty.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|integer',
                'product_code' => 'required|string|max:191',
                'product_name' => 'required|string|max:191',
                'serial_no' => 'required|string|max:191|unique:product_warranty,serial_no',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Prepare data
            $data = $validator->validated();

            $productWarranty = ProductWarranty::create($data);

            DB::commit();

            $formattedItem = $this->formatProductWarrantyItem($productWarranty);

            return response()->json([
                'success' => true,
                'data' => $formattedItem,
                'message' => 'Product warranty created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create product warranty: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product warranty',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified product warranty.
     */
    public function show($id)
    {
        try {
            $productWarranty = ProductWarranty::findOrFail($id);
            $formattedItem = $this->formatProductWarrantyItem($productWarranty);

            return response()->json([
                'success' => true,
                'data' => $formattedItem
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product warranty not found'
            ], 404);
        }
    }

    /**
     * Update the specified product warranty.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $productWarranty = ProductWarranty::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'product_id' => 'sometimes|required|integer',
                'product_code' => 'sometimes|required|string|max:191',
                'product_name' => 'sometimes|required|string|max:191',
                'serial_no' => 'sometimes|required|string|max:191|unique:product_warranty,serial_no,' . $id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $productWarranty->update($validator->validated());

            DB::commit();

            $formattedItem = $this->formatProductWarrantyItem($productWarranty->fresh());

            return response()->json([
                'success' => true,
                'data' => $formattedItem,
                'message' => 'Product warranty updated successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Product warranty not found'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update product warranty: ' . $e->getMessage(), [
                'exception' => $e,
                'id' => $id,
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product warranty',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified product warranty.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $productWarranty = ProductWarranty::findOrFail($id);
            $productWarranty->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product warranty deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Product warranty not found'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete product warranty: ' . $e->getMessage(), [
                'exception' => $e,
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product warranty',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Bulk delete product warranties.
     */
    public function bulkDelete(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:product_warranty,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $count = ProductWarranty::whereIn('id', $request->ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $count . ' records deleted successfully.',
                'data' => ['deleted_count' => $count]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk delete product warranties: ' . $e->getMessage(), [
                'exception' => $e,
                'ids' => $request->ids
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete records',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get statistics for product warranties
     */
    public function statistics()
    {
        try {
            $total = ProductWarranty::count();

            // Product loan stats

            // Unique products count
            $uniqueProducts = ProductWarranty::distinct('product_id')->count('product_id');

            // Recent records (last 30 days)
            $recentRecords = ProductWarranty::where('created_at', '>=', now()->subDays(30))->count();


            return response()->json([
                'success' => true,
                'data' => [
                    'total_records' => $total,
                    'unique_products' => $uniqueProducts,
                    'recent_records' => $recentRecords,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get statistics: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Export product warranties to CSV
     */
    public function export(Request $request)
    {
        try {
            $query = ProductWarranty::query();

            // Apply filters
            $query->filter($request);

            // Sorting
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $allowedSortFields = ['id', 'product_id', 'product_code', 'product_name', 'serial_no', 'created_at', 'updated_at'];
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection);
            }

            $data = $query->get();

            // Generate CSV
            $filename = 'product_warranty_export_' . date('Y-m-d_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function() use ($data) {
                $file = fopen('php://output', 'w');

                // Add UTF-8 BOM for Excel compatibility
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                // Add headers
                fputcsv($file, [
                    'ID',
                    'Product ID',
                    'Product Code',
                    'Product Name',
                    'Serial No',
                    'Created At',
                    'Updated At'
                ]);

                // Add data
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->id,
                        $row->product_id,
                        $row->product_code,
                        $row->product_name,
                        $row->serial_no,
                        $row->created_at->format('d/m/Y H:i:s'),
                        $row->updated_at->format('d/m/Y H:i:s')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Failed to export product warranties: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Generate next serial number
     */
    public function generateSerialNo()
    {
        try {
            $prefix = 'PW-';
            $year = date('Y');
            $month = date('m');

            // Get the last record for current year/month
            $lastRecord = ProductWarranty::where('serial_no', 'like', $prefix . $year . $month . '%')
                ->orderBy('id', 'desc')
                ->first();

            if ($lastRecord) {
                // Extract the sequence number
                preg_match('/' . $prefix . $year . $month . '(\d+)/', $lastRecord->serial_no, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $nextNumber = '0001';
            }

            $serialNo = $prefix . $year . $month . $nextNumber;

            return response()->json([
                'success' => true,
                'data' => [
                    'serial_no' => $serialNo,
                    'serialNo' => $serialNo,
                    'prefix' => $prefix,
                    'year' => $year,
                    'month' => $month,
                    'sequence' => $nextNumber
                ],
                'message' => 'Serial number generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate serial number: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate serial number',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Check if serial number exists
     */
    public function checkSerialNo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'serial_no' => 'required|string|max:191'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $exists = ProductWarranty::where('serial_no', $request->serial_no)->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'exists' => $exists,
                    'serial_no' => $request->serial_no
                ],
                'message' => $exists ? 'Serial number exists' : 'Serial number is available'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check serial number: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check serial number',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Format ProductWarranty item
     */
    private function formatProductWarrantyItem($productWarranty)
    {
        if (!$productWarranty) {
            return null;
        }

        $formatted = [
            'id' => $productWarranty->id,
            'product_id' => $productWarranty->product_id,
            'product_code' => $productWarranty->product_code,
            'product_name' => $productWarranty->product_name,
            'serial_no' => $productWarranty->serial_no,
            'created_at' => $productWarranty->created_at->format('d/m/Y'),
            'created_at_raw' => $productWarranty->created_at->toISOString(),
            'created_at_datetime' => $productWarranty->created_at->format('d/m/Y H:i:s'),
            'updated_at' => $productWarranty->updated_at->format('d/m/Y H:i:s'),
            'updated_at_raw' => $productWarranty->updated_at->toISOString(),
        ];

        // Add related product data if loaded
        if ($productWarranty->relationLoaded('product') && $productWarranty->product) {
            $product = $productWarranty->product;

            $formatted['product_details'] = [
                'id' => $product->id,
                'product_code' => $product->product_code,
                'product_name' => $product->product_name,
                'cat_name' => $product->category_name ?? null,
                'sup_name' => $product->sup_name ?? null,
                'price' => $product->price ?? null,
                'colour' => $product->colour ?? null,
                'is_care' => $product->is_care ?? null,
            ];
        }

        return $formatted;
    }

    // In your ProductWarrantyController.php
    public function getAvailableWarranties(Request $request)
    {
        $query = ProductWarranty::query()
            ->join('products', 'product_warranties.product_id', '=', 'products.id')
            ->join('categories', 'products.cat_id', '=', 'categories.id')
            ->select(
                'product_warranties.*',
                'products.product_name',
                'products.product_code',
                'products.price',
                'categories.name as category_name'
            )
            ->whereNotIn('product_warranties.id', function($q) {
                $q->select('i_qvca_id')
                ->from('care_warranties')
                ->whereNotNull('i_qvca_id');
            });

        // Filter by category
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('products.cat_id', $request->category_id);
        }

        // Optional: Filter by status if you have a status column
        if ($request->has('status') && !empty($request->status)) {
            $query->where('product_warranties.status', $request->status);
        }

        $warranties = $query->orderBy('product_warranties.created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $warranties
        ]);
    }

    // In ProductWarrantyController.php
    public function getByCategory(Request $request)
    {
        $categoryId = $request->category_id;

        $warranties = DB::table('product_warranty')
            ->join('products', 'product_warranty.product_id', '=', 'products.id')
            ->join('categories', 'products.cat_id', '=', 'categories.id')
            ->select(
                'product_warranty.*',
                'products.product_name',
                'products.product_code',
                'categories.name as category_name',
                'categories.id as category_id'
            )
            ->where('products.cat_id', $categoryId)
            ->orderBy('product_warranty.created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $warranties
        ]);
    }
}
