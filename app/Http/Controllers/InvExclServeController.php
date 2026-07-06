<?php

namespace App\Http\Controllers;

use App\Models\InvExclServe;
use App\Models\MasterSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvExclServeController extends Controller
{
    public function index(Request $request)
    {
        $query = InvExclServe::with(['masterSku']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'LIKE', "%{$search}%")
                    ->orWhere('sku_code', 'LIKE', "%{$search}%")
                    ->orWhere('inv_excl_serve', 'LIKE', "%{$search}%");
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
        $query = InvExclServe::with(['masterSku']);

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
        $invExclServe = InvExclServe::with(['masterSku'])->find($id);

        if (!$invExclServe) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $invExclServe]);
    }

    public function edit($id)
    {
        $invExclServe = InvExclServe::with(['masterSku'])->find($id);

        if (!$invExclServe) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'inv_excl_serve' => $invExclServe,
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
            $nextId = InvExclServe::count() + 1;
            $invExclServeCode = 'IE-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $invExclServe = InvExclServe::create([
                'inv_excl_serve' => $invExclServeCode,
                'serve_data_id' => $request->serve_data_id ?? 0,
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
                'data' => $invExclServe->load('masterSku'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create inventory record', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $invExclServe = InvExclServe::find($id);

        if (!$invExclServe) {
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
            $invExclServe->update($request->only([
                'sku_code', 'item_name', 'unit_cost', 'max_stock', 'current_stock', 'to_restock', 'status',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Inventory record updated successfully',
                'data' => $invExclServe->fresh()->load('masterSku'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update inventory record', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $invExclServe = InvExclServe::find($id);

        if (!$invExclServe) {
            return response()->json(['success' => false, 'message' => 'Inventory record not found'], 404);
        }

        try {
            $invExclServe->delete();
            return response()->json(['success' => true, 'message' => 'Inventory record deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete inventory record', 'error' => $e->getMessage()], 500);
        }
    }

    public function statistics()
    {
        $totalItems = InvExclServe::count();
        $totalStock = InvExclServe::sum('current_stock');
        $lowStockCount = InvExclServe::where('current_stock', '<', 5)->count();

        $byStatus = InvExclServe::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_items' => $totalItems,
                'total_stock' => (int) $totalStock,
                'low_stock_count' => $lowStockCount,
                'by_status' => $byStatus,
            ],
        ]);
    }
}
