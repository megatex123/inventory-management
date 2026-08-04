<?php

namespace App\Http\Controllers;

use App\Models\InvCare;
use App\Models\MasterSku;
use App\Models\Categories;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;

class InvCareController extends Controller
{
    use FiltersSortsAndPaginates;

    public function index(Request $request)
    {
        $query = InvCare::with(['masterSku', 'categoryLookup']);

        $this->applyEqualsFilter($query, $request, 'category', 'category');
        $this->applyEqualsFilter($query, $request, 'status', 'status');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('item_name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('sku_code', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('inv_care', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('manufacturer', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply($query, $request, ['item_name', 'sku_code', 'unit_cost', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    public function search(Request $request)
    {
        $query = InvCare::with(['masterSku']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('item_name', 'LIKE', "%{$search}%");
        }

        $results = $query->paginate($request->get('per_page', 10));

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

    public function show($id)
    {
        $invCare = InvCare::with(['masterSku', 'categoryLookup'])->find($id);

        if (!$invCare) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $invCare]);
    }

    public function edit($id)
    {
        $invCare = InvCare::with(['masterSku', 'categoryLookup'])->find($id);

        if (!$invCare) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'inv_care' => $invCare,
                'master_skus' => MasterSku::orderBy('product_name')->get(),
                'categories' => Categories::orderBy('name')->get(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sku_code' => 'required|string|max:100|exists:master_sku,sku_code',
            'item_name' => 'required|string|max:100',
            'unit_cost' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'current_stock' => 'required|integer|min:0',
            'category' => 'nullable|exists:categories,id',
            'status' => 'nullable|integer',
            'manufacturer' => 'nullable|string|max:100',
            'warranty_starts' => 'nullable|date',
            'warranty_duration' => 'nullable|integer|min:0',
            'warranty_ends' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $categoryName = optional(Categories::find($request->category))->name ?? 'MISC';
            $invCareCode = BusinessId::next('inv_care', 'inv_care', "CINV-{$categoryName}-", 6);

            $invCare = InvCare::create([
                'inv_care' => $invCareCode,
                'care_id' => $request->care_id ?? 0,
                'sku_code' => $request->sku_code,
                'item_name' => $request->item_name,
                'unit_cost' => $request->unit_cost ?? 0,
                'max_stock' => $request->max_stock ?? 0,
                'current_stock' => $request->current_stock,
                'category' => $request->category ?? 0,
                'status' => $request->status ?? 1,
                'generate_id' => $request->generate_id ?? 0,
                'serial_label' => $request->serial_label ?? 0,
                'warranty_starts' => $request->warranty_starts ?? now(),
                'warranty_duration' => $request->warranty_duration ?? 0,
                'warranty_ends' => $request->warranty_ends ?? now(),
                'manufacturer' => $request->manufacturer ?? '',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory record created successfully',
                'data' => $invCare->load('masterSku'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create inventory record', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $invCare = InvCare::find($id);

        if (!$invCare) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'sku_code' => 'required|string|max:100|exists:master_sku,sku_code',
            'item_name' => 'required|string|max:100',
            'unit_cost' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'current_stock' => 'required|integer|min:0',
            'category' => 'nullable|exists:categories,id',
            'status' => 'nullable|integer',
            'manufacturer' => 'nullable|string|max:100',
            'warranty_starts' => 'nullable|date',
            'warranty_duration' => 'nullable|integer|min:0',
            'warranty_ends' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $invCare->update($request->only([
                'sku_code', 'item_name', 'unit_cost', 'max_stock', 'current_stock', 'category',
                'status', 'manufacturer', 'warranty_starts', 'warranty_duration', 'warranty_ends',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Inventory record updated successfully',
                'data' => $invCare->fresh()->load('masterSku'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update inventory record', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $invCare = InvCare::find($id);

        if (!$invCare) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        try {
            $invCare->delete();
            return response()->json(['success' => true, 'message' => 'Inventory record deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete inventory record', 'error' => $e->getMessage()], 500);
        }
    }

    public function statistics()
    {
        $totalItems = InvCare::count();
        $totalStock = InvCare::sum('current_stock');
        $lowStockCount = InvCare::where('current_stock', '<', 5)->count();

        $byCategory = InvCare::join('categories', 'inv_care.category', '=', 'categories.id')
            ->select('categories.id', 'categories.name', DB::raw('COUNT(*) as count'), DB::raw('SUM(inv_care.current_stock) as total_stock'))
            ->groupBy('categories.id', 'categories.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_items' => $totalItems,
                'total_stock' => (int) $totalStock,
                'low_stock_count' => $lowStockCount,
                'by_category' => $byCategory,
            ],
        ]);
    }
}
