<?php

namespace App\Http\Controllers;

use App\Models\ThreadOrder;
use App\Models\ThreadOrderItem;
use App\Models\ThreadBomHeader;
use App\Models\Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ThreadOrderController extends Controller
{
    const STATUSES = ['pending', 'cutting', 'sleeving', 'qc', 'complete'];

    public function index(Request $request)
    {
        $query = ThreadOrder::with(['customer', 'order', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $query->where('thread_order_id', 'LIKE', "%{$request->search}%");
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
        $query = ThreadOrder::with(['customer']);

        if ($request->filled('search')) {
            $query->where('thread_order_id', 'LIKE', "%{$request->search}%");
        }

        $results = $query->paginate($request->get('per_page', 10));

        return response()->json(['success' => true, 'data' => $results->items()]);
    }

    public function show($id)
    {
        $order = ThreadOrder::with(['customer', 'order', 'items'])->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Thread order not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function edit($id)
    {
        $order = ThreadOrder::with(['items'])->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Thread order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'thread_order' => $order,
                'customers' => Customers::orderBy('full_name')->get(),
                'psu_brands' => ThreadBomHeader::select('psu_brand')->distinct()->pluck('psu_brand'),
                'cable_types' => ThreadBomController::CABLE_TYPES,
                'statuses' => self::STATUSES,
            ],
        ]);
    }

    /**
     * Resolve the BOM for a line item the same way
     * ThreadBomController::resolve() does, for use at order-creation time
     * (where we need the actual component list to snapshot, not just a preview).
     */
    private function resolveComponents($psuBrand, $cableType, $colourVariant = null)
    {
        $query = ThreadBomHeader::with(['lines'])
            ->where('psu_brand', $psuBrand)
            ->where('cable_type', $cableType);

        if ($colourVariant) {
            $query->where(function ($q) use ($colourVariant) {
                $q->where('colour_variant', $colourVariant)
                    ->orWhere('colour_variant', 'Not');
            });
        }

        $headers = $query->get();
        $components = [];
        $totalCost = 0;

        foreach ($headers as $header) {
            foreach ($header->lines as $line) {
                $lineCost = $line->qty_per_cable * $line->unit_cost;
                $totalCost += $lineCost;
                $components[] = [
                    'sku_code' => $line->sku_code,
                    'item_name' => $line->item_name,
                    'qty_per_cable' => $line->qty_per_cable,
                    'unit_cost' => (float) $line->unit_cost,
                    'line_cost' => (float) $lineCost,
                ];
            }
        }

        return ['components' => $components, 'total_cost' => (float) $totalCost];
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'status' => 'nullable|string|in:' . implode(',', self::STATUSES),
            'items' => 'required|array|min:1',
            'items.*.cable_type' => 'required|string',
            'items.*.psu_brand' => 'required|string',
            'items.*.colour_variant' => 'nullable|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.wire_length_cm' => 'nullable|numeric|min:0',
            'items.*.sleeve_length_cm' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $nextId = ThreadOrder::count() + 1;
            $threadOrderId = 'QVTD-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $order = ThreadOrder::create([
                'thread_order_id' => $threadOrderId,
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'status' => $request->status ?? 'pending',
            ]);

            foreach ($request->items as $line) {
                $resolved = $this->resolveComponents($line['psu_brand'], $line['cable_type'], $line['colour_variant'] ?? null);
                $unitPrice = $line['unit_price'] ?? $resolved['total_cost'];

                ThreadOrderItem::create([
                    'thread_order_id' => $order->id,
                    'cable_type' => $line['cable_type'],
                    'psu_brand' => $line['psu_brand'],
                    'colour_variant' => $line['colour_variant'] ?? null,
                    'qty' => $line['qty'],
                    'wire_length_cm' => $line['wire_length_cm'] ?? null,
                    'sleeve_length_cm' => $line['sleeve_length_cm'] ?? null,
                    'resolved_components' => $resolved['components'],
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $line['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thread order created successfully',
                'data' => $order->load('items'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create thread order', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $order = ThreadOrder::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Thread order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'status' => 'nullable|string|in:' . implode(',', self::STATUSES),
            'items' => 'required|array|min:1',
            'items.*.cable_type' => 'required|string',
            'items.*.psu_brand' => 'required|string',
            'items.*.colour_variant' => 'nullable|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.wire_length_cm' => 'nullable|numeric|min:0',
            'items.*.sleeve_length_cm' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $newStatus = $request->status ?? $order->status;

            $order->update([
                'customer_id' => $request->customer_id,
                'order_id' => $request->order_id,
                'status' => $newStatus,
                'completed_at' => $newStatus === 'complete' ? ($order->completed_at ?? now()) : $order->completed_at,
                'warranty_ends_at' => $newStatus === 'complete' ? ($order->warranty_ends_at ?? now()->addDays(90)) : $order->warranty_ends_at,
            ]);

            $order->items()->delete();
            foreach ($request->items as $line) {
                $resolved = $this->resolveComponents($line['psu_brand'], $line['cable_type'], $line['colour_variant'] ?? null);
                $unitPrice = $line['unit_price'] ?? $resolved['total_cost'];

                ThreadOrderItem::create([
                    'thread_order_id' => $order->id,
                    'cable_type' => $line['cable_type'],
                    'psu_brand' => $line['psu_brand'],
                    'colour_variant' => $line['colour_variant'] ?? null,
                    'qty' => $line['qty'],
                    'wire_length_cm' => $line['wire_length_cm'] ?? null,
                    'sleeve_length_cm' => $line['sleeve_length_cm'] ?? null,
                    'resolved_components' => $resolved['components'],
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $line['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thread order updated successfully',
                'data' => $order->fresh()->load('items'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update thread order', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $order = ThreadOrder::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Thread order not found'], 404);
        }

        try {
            $order->delete();
            return response()->json(['success' => true, 'message' => 'Thread order deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete thread order', 'error' => $e->getMessage()], 500);
        }
    }

    public function statistics()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_orders' => ThreadOrder::count(),
                'in_progress' => ThreadOrder::whereIn('status', ['pending', 'cutting', 'sleeving', 'qc'])->count(),
                'completed_orders' => ThreadOrder::where('status', 'complete')->count(),
                'total_revenue' => (float) ThreadOrderItem::sum('line_total'),
            ],
        ]);
    }
}
