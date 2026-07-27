<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Models\Order;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{
    private function validationRules()
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'plus_order_id' => 'nullable|exists:plus_orders,id',
            'merch_order_id' => 'nullable|exists:merch_orders,id',
            'thread_order_id' => 'nullable|exists:thread_orders,id',
            'refund_amount' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'payment_type' => 'nullable|string|max:50',
            'cash_journal' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'refunded_at' => 'nullable|date',
        ];
    }

    private function relations()
    {
        return ['customer', 'order', 'plusOrder', 'merchOrder', 'threadOrder'];
    }

    public function index(Request $request)
    {
        $query = Refund::with($this->relations());

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $query->where('refund_id', 'LIKE', "%{$request->search}%");
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

    public function orderOptions(Request $request)
    {
        $query = Order::select('id', 'order_id');

        if ($request->filled('search')) {
            $query->where('order_id', 'LIKE', "%{$request->search}%");
        }

        $results = $query->orderByDesc('id')->paginate($request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $results->items()]);
    }

    public function statistics()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_refunds' => Refund::count(),
                'total_refund_amount' => (float) Refund::sum('refund_amount'),
                'refunds_this_month' => Refund::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'average_refund_amount' => (float) Refund::avg('refund_amount'),
            ],
        ]);
    }

    public function show($id)
    {
        $refund = Refund::with($this->relations())->find($id);

        if (!$refund) {
            return response()->json(['success' => false, 'message' => 'Refund not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $refund]);
    }

    public function edit($id)
    {
        $refund = Refund::with($this->relations())->find($id);

        if (!$refund) {
            return response()->json(['success' => false, 'message' => 'Refund not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $refund]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->validationRules());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $refundId = BusinessId::next('refunds', 'refund_id', 'QV-REFD-', 6);

        $refund = Refund::create([
            'refund_id' => $refundId,
            'customer_id' => $request->customer_id,
            'order_id' => $request->order_id,
            'plus_order_id' => $request->plus_order_id,
            'merch_order_id' => $request->merch_order_id,
            'thread_order_id' => $request->thread_order_id,
            'refund_amount' => $request->refund_amount,
            'deposit_amount' => $request->deposit_amount,
            'payment_type' => $request->payment_type,
            'cash_journal' => $request->boolean('cash_journal'),
            'notes' => $request->notes,
            'refunded_at' => $request->refunded_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Refund created successfully',
            'data' => $refund->load($this->relations()),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $refund = Refund::find($id);

        if (!$refund) {
            return response()->json(['success' => false, 'message' => 'Refund not found'], 404);
        }

        $validator = Validator::make($request->all(), $this->validationRules());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $refund->update([
            'customer_id' => $request->customer_id,
            'order_id' => $request->order_id,
            'plus_order_id' => $request->plus_order_id,
            'merch_order_id' => $request->merch_order_id,
            'thread_order_id' => $request->thread_order_id,
            'refund_amount' => $request->refund_amount,
            'deposit_amount' => $request->deposit_amount,
            'payment_type' => $request->payment_type,
            'cash_journal' => $request->boolean('cash_journal'),
            'notes' => $request->notes,
            'refunded_at' => $request->refunded_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Refund updated successfully',
            'data' => $refund->fresh()->load($this->relations()),
        ]);
    }

    public function destroy($id)
    {
        $refund = Refund::find($id);

        if (!$refund) {
            return response()->json(['success' => false, 'message' => 'Refund not found'], 404);
        }

        try {
            $refund->delete();
            return response()->json(['success' => true, 'message' => 'Refund deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete refund', 'error' => $e->getMessage()], 500);
        }
    }
}
