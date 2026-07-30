<?php

namespace App\Http\Controllers;

use App\Models\MerchItem;
use App\Models\MasterSku;
use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MerchItemController extends Controller
{
    use FiltersSortsAndPaginates;

    public function index(Request $request)
    {
        $query = MerchItem::with(['masterSku']);

        $this->applyEqualsFilter($query, $request, 'status', 'status');

        $isExclusive = $request->input('is_exclusive');
        if (is_scalar($isExclusive) && $isExclusive !== '') {
            $query->where('is_exclusive', (bool) $isExclusive);
        }

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('sku_code', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('item_code', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply($query, $request, ['item_code', 'name', 'sku_code', 'retail_price', 'member_discount_price', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    public function search(Request $request)
    {
        $query = MerchItem::with(['masterSku']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $results = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $results->items(),
        ]);
    }

    public function show($id)
    {
        $item = MerchItem::with(['masterSku'])->find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Merch item not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $item]);
    }

    public function edit($id)
    {
        $item = MerchItem::with(['masterSku'])->find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Merch item not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'merch_item' => $item,
                'master_skus' => MasterSku::orderBy('product_name')->get(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sku_code' => 'required|string|max:100|exists:master_sku,sku_code',
            'name' => 'required|string|max:191',
            'retail_price' => 'required|numeric|min:0',
            'member_discount_price' => 'nullable|numeric|min:0',
            'is_exclusive' => 'nullable|boolean',
            'status' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $nextId = MerchItem::count() + 1;
            $itemCode = 'MI-QVMR-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $item = MerchItem::create([
                'item_code' => $itemCode,
                'sku_code' => $request->sku_code,
                'name' => $request->name,
                'retail_price' => $request->retail_price,
                'member_discount_price' => $request->member_discount_price,
                'is_exclusive' => $request->boolean('is_exclusive'),
                'status' => $request->status ?? 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Merch item created successfully',
                'data' => $item->load('masterSku'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create merch item', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $item = MerchItem::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Merch item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'sku_code' => 'required|string|max:100|exists:master_sku,sku_code',
            'name' => 'required|string|max:191',
            'retail_price' => 'required|numeric|min:0',
            'member_discount_price' => 'nullable|numeric|min:0',
            'is_exclusive' => 'nullable|boolean',
            'status' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $item->update($request->only([
                'sku_code', 'name', 'retail_price', 'member_discount_price', 'is_exclusive', 'status',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Merch item updated successfully',
                'data' => $item->fresh()->load('masterSku'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update merch item', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $item = MerchItem::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Merch item not found'], 404);
        }

        try {
            $item->delete();
            return response()->json(['success' => true, 'message' => 'Merch item deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete merch item', 'error' => $e->getMessage()], 500);
        }
    }

    public function statistics()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_items' => MerchItem::count(),
                'exclusive_items' => MerchItem::where('is_exclusive', true)->count(),
                'general_items' => MerchItem::where('is_exclusive', false)->count(),
                'active_items' => MerchItem::where('status', 1)->count(),
            ],
        ]);
    }
}
