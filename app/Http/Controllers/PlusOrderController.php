<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use App\Models\PlusOrder;
use App\Models\PlusOrderItem;
use App\Models\PlusService;
use App\Models\Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PlusOrderController extends Controller
{
    use FiltersSortsAndPaginates;

    public function index(Request $request)
    {
        $query = PlusOrder::with(['customer', 'order', 'items.plusService']);

        $this->applyEqualsFilter($query, $request, 'status', 'status');
        $this->applyEqualsFilter($query, $request, 'customer_id', 'customer_id');
        $this->applyLikeFilter($query, $request, 'search', 'plus_order_id');

        $this->resolveSortAndApply($query, $request, ['plus_order_id', 'status', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    public function search(Request $request)
    {
        $query = PlusOrder::with(['customer']);

        if ($request->filled('search')) {
            $query->where('plus_order_id', 'LIKE', "%{$request->search}%");
        }

        $results = $query->paginate($request->get('per_page', 10));

        return response()->json(['success' => true, 'data' => $results->items()]);
    }

    public function show($id)
    {
        $order = PlusOrder::with(['customer', 'order', 'items.plusService'])->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Plus order not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function edit($id)
    {
        $order = PlusOrder::with(['items.plusService'])->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Plus order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'plus_order' => $order,
                'customers' => Customers::orderBy('full_name')->get(),
                'plus_services' => PlusService::where('is_active', true)->orderBy('name')->get(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'status' => 'nullable|string|max:20',
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.plus_service_id' => 'required|exists:plus_services,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $nextId = PlusOrder::count() + 1;
            $plusOrderId = 'QVPL-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $order = PlusOrder::create([
                'plus_order_id' => $plusOrderId,
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'status' => $request->status ?? 'pending',
                'scheduled_at' => $request->scheduled_at,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $line) {
                $service = PlusService::findOrFail($line['plus_service_id']);

                PlusOrderItem::create([
                    'plus_order_id' => $order->id,
                    'plus_service_id' => $service->id,
                    'qty' => $line['qty'],
                    'unit_price' => $service->price,
                    'line_total' => $service->price * $line['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Plus order created successfully',
                'data' => $order->load('items.plusService'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create plus order', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $order = PlusOrder::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Plus order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'status' => 'nullable|string|max:20',
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.plus_service_id' => 'required|exists:plus_services,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'status' => $request->status ?? $order->status,
                'scheduled_at' => $request->scheduled_at,
                'notes' => $request->notes,
                'completed_at' => $request->status === 'completed' ? ($order->completed_at ?? now()) : $order->completed_at,
            ]);

            $order->items()->delete();
            foreach ($request->items as $line) {
                $service = PlusService::findOrFail($line['plus_service_id']);

                PlusOrderItem::create([
                    'plus_order_id' => $order->id,
                    'plus_service_id' => $service->id,
                    'qty' => $line['qty'],
                    'unit_price' => $service->price,
                    'line_total' => $service->price * $line['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Plus order updated successfully',
                'data' => $order->fresh()->load('items.plusService'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update plus order', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $order = PlusOrder::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Plus order not found'], 404);
        }

        try {
            $order->delete();
            return response()->json(['success' => true, 'message' => 'Plus order deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete plus order', 'error' => $e->getMessage()], 500);
        }
    }

    public function statistics()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_orders' => PlusOrder::count(),
                'pending_orders' => PlusOrder::where('status', 'pending')->count(),
                'scheduled_orders' => PlusOrder::where('status', 'scheduled')->count(),
                'completed_orders' => PlusOrder::where('status', 'completed')->count(),
                'total_revenue' => (float) PlusOrderItem::sum('line_total'),
            ],
        ]);
    }
}
