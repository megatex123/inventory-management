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
                    ->orWhere('manufacturer', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('serial_number', 'LIKE', '%' . $escaped . '%');
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

    /**
     * Units of the given category that are still available to hand out as
     * a warranty replacement -- Active, not Occupied. Each row is now one
     * serialized physical unit, not a quantity, so "available" means the
     * unit itself hasn't already been used, not a stock count > 0.
     */
    public function getByCategory(Request $request)
    {
        $items = InvCare::with('categoryLookup')
            ->where('category', $request->category_id)
            ->where('status', InvCare::STATUS_ACTIVE)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'inv_care' => $item->inv_care,
                    'item_name' => $item->item_name,
                    'sku_code' => $item->sku_code,
                    'serial_number' => $item->serial_number,
                    'category_name' => optional($item->categoryLookup)->name,
                    'category_id' => $item->category,
                    'created_at' => $item->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $items,
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
            'category' => 'nullable|exists:categories,id',
            'status' => 'nullable|integer|between:1,8',
            'serial_number' => 'nullable|string|max:100',
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
                'category' => $request->category ?? 0,
                'status' => $request->status ?? InvCare::STATUS_ACTIVE,
                'generate_id' => $request->generate_id ?? 0,
                'serial_number' => $request->serial_number,
                // Leave unset -- fabricating "now" here silently claims a
                // warranty period that was never actually assigned yet.
                'warranty_starts' => $request->warranty_starts,
                'warranty_duration' => $request->warranty_duration ?? 0,
                'warranty_ends' => $request->warranty_ends,
                'manufacturer' => $request->manufacturer,
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
            'category' => 'nullable|exists:categories,id',
            'status' => 'nullable|integer|between:1,8',
            'serial_number' => 'nullable|string|max:100',
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
                'sku_code', 'item_name', 'unit_cost', 'category',
                'status', 'serial_number', 'manufacturer', 'warranty_starts', 'warranty_duration', 'warranty_ends',
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
        $occupiedCount = InvCare::where('status', InvCare::STATUS_OCCUPIED)->count();
        $activeCount = InvCare::where('status', InvCare::STATUS_ACTIVE)->count();

        $byCategory = InvCare::join('categories', 'inv_care.category', '=', 'categories.id')
            ->select('categories.id', 'categories.name', DB::raw('COUNT(*) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_items' => $totalItems,
                'active_count' => $activeCount,
                'occupied_count' => $occupiedCount,
                'by_category' => $byCategory,
            ],
        ]);
    }
}
