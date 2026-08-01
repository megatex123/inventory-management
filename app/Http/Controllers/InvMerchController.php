<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use App\Models\InvMerch;
use App\Models\MasterSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvMerchController extends Controller
{
    use FiltersSortsAndPaginates;

    public function index(Request $request)
    {
        $query = InvMerch::with(['masterSku']);

        $this->applyEqualsFilter($query, $request, 'status', 'status');

        $isExclusive = $request->input('is_exclusive');
        if (is_scalar($isExclusive) && $isExclusive !== '') {
            $query->where('is_exclusive', (bool) $isExclusive);
        }

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('item_name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('sku_code', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('inv_merch_id', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply($query, $request, ['inv_merch_id', 'item_name', 'sku_code', 'unit_cost', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    public function search(Request $request)
    {
        $query = InvMerch::with(['masterSku']);

        if ($request->filled('search')) {
            $query->where('item_name', 'LIKE', "%{$request->search}%");
        }

        $results = $query->paginate($request->get('per_page', 10));

        return response()->json(['success' => true, 'data' => $results->items()]);
    }

    public function show($id)
    {
        $item = InvMerch::with(['masterSku'])->find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $item]);
    }

    public function edit($id)
    {
        $item = InvMerch::with(['masterSku'])->find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'inv_merch' => $item,
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
            'is_exclusive' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $nextId = InvMerch::count() + 1;
            $code = 'I-QVMR-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $item = InvMerch::create([
                'inv_merch_id' => $code,
                'sku_code' => $request->sku_code,
                'item_name' => $request->item_name,
                'unit_cost' => $request->unit_cost ?? 0,
                'max_stock' => $request->max_stock ?? 0,
                'current_stock' => $request->current_stock,
                'to_restock' => $request->to_restock ?? 0,
                'status' => $request->status ?? 1,
                'generate_id' => $request->generate_id ?? 0,
                'is_exclusive' => $request->boolean('is_exclusive'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory record created successfully',
                'data' => $item->load('masterSku'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create inventory record', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $item = InvMerch::find($id);

        if (!$item) {
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
            'is_exclusive' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $item->update($request->only([
                'sku_code', 'item_name', 'unit_cost', 'max_stock', 'current_stock', 'to_restock', 'status', 'is_exclusive',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Inventory record updated successfully',
                'data' => $item->fresh()->load('masterSku'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update inventory record', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $item = InvMerch::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        try {
            $item->delete();
            return response()->json(['success' => true, 'message' => 'Inventory record deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete inventory record', 'error' => $e->getMessage()], 500);
        }
    }

    public function statistics()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_items' => InvMerch::count(),
                'total_stock' => (int) InvMerch::sum('current_stock'),
                'low_stock_count' => InvMerch::whereColumn('current_stock', '<', 'to_restock')->count(),
                'exclusive_items' => InvMerch::where('is_exclusive', true)->count(),
                'general_items' => InvMerch::where('is_exclusive', false)->count(),
            ],
        ]);
    }
}
