<?php

namespace App\Http\Controllers;

use App\Models\MerchOrder;
use App\Models\MerchOrderItem;
use App\Models\MerchItem;
use App\Models\Customers;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MerchOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = MerchOrder::with(['customer', 'order', 'items.merchItem']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $query->where('merch_order_id', 'LIKE', "%{$request->search}%");
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
        $query = MerchOrder::with(['customer']);

        if ($request->filled('search')) {
            $query->where('merch_order_id', 'LIKE', "%{$request->search}%");
        }

        $results = $query->paginate($request->get('per_page', 10));

        return response()->json(['success' => true, 'data' => $results->items()]);
    }

    public function show($id)
    {
        $order = MerchOrder::with(['customer', 'order', 'items.merchItem.masterSku'])->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Merch order not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function edit($id)
    {
        $order = MerchOrder::with(['items.merchItem'])->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Merch order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'merch_order' => $order,
                'customers' => Customers::orderBy('full_name')->get(),
                'merch_items' => MerchItem::where('status', 1)->orderBy('name')->get(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'status' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.merch_item_id' => 'required|exists:merch_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.discount_applied' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $nextId = MerchOrder::count() + 1;
            $merchOrderId = 'QVMOP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $order = MerchOrder::create([
                'merch_order_id' => $merchOrderId,
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'status' => $request->status ?? 'pending',
                'purchased_at' => now(),
            ]);

            foreach ($request->items as $line) {
                $merchItem = MerchItem::findOrFail($line['merch_item_id']);
                $discountApplied = (bool) ($line['discount_applied'] ?? false);
                $unitPrice = $discountApplied && $merchItem->member_discount_price !== null
                    ? $merchItem->member_discount_price
                    : $merchItem->retail_price;

                MerchOrderItem::create([
                    'merch_order_id' => $order->id,
                    'merch_item_id' => $merchItem->id,
                    'qty' => $line['qty'],
                    'unit_price' => $unitPrice,
                    'discount_applied' => $discountApplied,
                    'line_total' => $unitPrice * $line['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Merch order created successfully',
                'data' => $order->load('items.merchItem'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create merch order', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $order = MerchOrder::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Merch order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'status' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.merch_item_id' => 'required|exists:merch_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.discount_applied' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $order->update($request->only(['customer_id', 'order_id', 'status']));

            $order->items()->delete();
            foreach ($request->items as $line) {
                $merchItem = MerchItem::findOrFail($line['merch_item_id']);
                $discountApplied = (bool) ($line['discount_applied'] ?? false);
                $unitPrice = $discountApplied && $merchItem->member_discount_price !== null
                    ? $merchItem->member_discount_price
                    : $merchItem->retail_price;

                MerchOrderItem::create([
                    'merch_order_id' => $order->id,
                    'merch_item_id' => $merchItem->id,
                    'qty' => $line['qty'],
                    'unit_price' => $unitPrice,
                    'discount_applied' => $discountApplied,
                    'line_total' => $unitPrice * $line['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Merch order updated successfully',
                'data' => $order->fresh()->load('items.merchItem'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update merch order', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $order = MerchOrder::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Merch order not found'], 404);
        }

        try {
            $order->delete();
            return response()->json(['success' => true, 'message' => 'Merch order deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete merch order', 'error' => $e->getMessage()], 500);
        }
    }

    public function statistics()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_orders' => MerchOrder::count(),
                'pending_orders' => MerchOrder::where('status', 'pending')->count(),
                'completed_orders' => MerchOrder::where('status', 'completed')->count(),
                'total_revenue' => (float) MerchOrderItem::sum('line_total'),
            ],
        ]);
    }
}
