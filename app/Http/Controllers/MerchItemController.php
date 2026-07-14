<?php

namespace App\Http\Controllers;

use App\Models\MerchItem;
use App\Models\MasterSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MerchItemController extends Controller
{
    public function index(Request $request)
    {
        $query = MerchItem::with(['masterSku']);

        if ($request->filled('is_exclusive')) {
            $query->where('is_exclusive', $request->boolean('is_exclusive'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('sku_code', 'LIKE', "%{$search}%")
                    ->orWhere('item_code', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy($request->get('order_by', 'created_at'), $request->get('order_direction', 'desc'));

        $results = $query->paginate($request->get('per_page', 15));

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
