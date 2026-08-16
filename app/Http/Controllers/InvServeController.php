<?php

namespace App\Http\Controllers;

use App\Models\InvServe;
use App\Models\MasterSku;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;

class InvServeController extends Controller
{
    use FiltersSortsAndPaginates;

    public function index(Request $request)
    {
        $query = InvServe::with('masterSku');

        $this->applyEqualsFilter($query, $request, 'status', 'status');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('item_name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('sku_code', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('inv_serve', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply($query, $request, ['item_name', 'sku_code', 'unit_cost', 'current_stock', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    public function search(Request $request)
    {
        $query = InvServe::with('masterSku');

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
        $invServe = InvServe::with('masterSku')->find($id);

        if (!$invServe) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $invServe]);
    }

    public function edit($id)
    {
        $invServe = InvServe::with('masterSku')->find($id);

        if (!$invServe) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'inv_serve' => $invServe,
                'master_skus' => MasterSku::orderBy('product_name')->get(),
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
            'to_restock' => 'nullable|integer|min:0',
            'status' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $invServeCode = BusinessId::next('inv_serve', 'inv_serve', 'IE-QVSE-', 6);

            $invServe = InvServe::create([
                'inv_serve' => $invServeCode,
                'sku_code' => $request->sku_code,
                'item_name' => $request->item_name,
                'unit_cost' => $request->unit_cost ?? 0,
                'max_stock' => $request->max_stock ?? 0,
                'current_stock' => $request->current_stock,
                'to_restock' => $request->to_restock ?? 0,
                'status' => $request->status ?? 1,
                'generate_id' => $request->generate_id ?? 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory record created successfully',
                'data' => $invServe->load('masterSku'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create inventory record', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $invServe = InvServe::find($id);

        if (!$invServe) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'sku_code' => 'required|string|max:100|exists:master_sku,sku_code',
            'item_name' => 'required|string|max:100',
            'unit_cost' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'current_stock' => 'required|integer|min:0',
            'to_restock' => 'nullable|integer|min:0',
            'status' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $invServe->update($request->only([
                'sku_code', 'item_name', 'unit_cost', 'max_stock', 'current_stock', 'to_restock', 'status',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Inventory record updated successfully',
                'data' => $invServe->fresh()->load('masterSku'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update inventory record', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $invServe = InvServe::find($id);

        if (!$invServe) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        try {
            $invServe->delete();
            return response()->json(['success' => true, 'message' => 'Inventory record deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete inventory record', 'error' => $e->getMessage()], 500);
        }
    }

    public function statistics()
    {
        $totalItems = InvServe::count();
        $totalStock = InvServe::sum('current_stock');
        $lowStockCount = InvServe::where('current_stock', '<', 5)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_items' => $totalItems,
                'total_stock' => (int) $totalStock,
                'low_stock_count' => $lowStockCount,
            ],
        ]);
    }
}
