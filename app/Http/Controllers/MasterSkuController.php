<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use App\Models\MasterSku;
use App\Models\Suppliers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MasterSkuController extends Controller
{
    use FiltersSortsAndPaginates;

    public function index(Request $request)
    {
        $query = MasterSku::with(['suppliers']);

        $this->applyEqualsFilter($query, $request, 'supplier_id', 'supplier_id');
        $this->applyEqualsFilter($query, $request, 'lkp_status_sku', 'lkp_status_sku');

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('sku_code', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('product_name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('from', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply($query, $request, ['sku_code', 'product_name', 'unit_type', 'cost', 'created_at'], 'created_at', 'id', ['cost'], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    public function search(Request $request)
    {
        $query = MasterSku::with(['suppliers']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sku_code', 'LIKE', "%{$search}%")
                    ->orWhere('product_name', 'LIKE', "%{$search}%");
            });
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
        $masterSku = MasterSku::with(['suppliers'])->find($id);

        if (!$masterSku) {
            return response()->json(['success' => false, 'message' => 'Master SKU not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $masterSku]);
    }

    public function edit($id)
    {
        $masterSku = MasterSku::with(['suppliers'])->find($id);

        if (!$masterSku) {
            return response()->json(['success' => false, 'message' => 'Master SKU not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'master_sku' => $masterSku,
                'suppliers' => Suppliers::orderBy('name')->get(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sku_code' => 'required|string|max:50|unique:master_sku,sku_code',
            'product_name' => 'nullable|string|max:50',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'product_raw_id' => 'nullable|exists:product_raw,id',
            'from' => 'nullable|string|max:50',
            'cost' => 'nullable|numeric|min:0',
            'unit_type' => 'nullable|string|max:50',
            'lkp_status_sku' => 'nullable|integer|between:1,7',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $masterSku = MasterSku::create([
                'sku_code' => $request->sku_code,
                'product_name' => $request->product_name,
                'supplier_id' => $request->supplier_id,
                'product_raw_id' => $request->product_raw_id,
                'from' => $request->from,
                'cost' => $request->cost,
                'unit_type' => $request->unit_type,
                'lkp_status_sku' => $request->lkp_status_sku ?? 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Master SKU created successfully',
                'data' => $masterSku->load('suppliers'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create master SKU', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $masterSku = MasterSku::find($id);

        if (!$masterSku) {
            return response()->json(['success' => false, 'message' => 'Master SKU not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'sku_code' => 'required|string|max:50|unique:master_sku,sku_code,' . $id,
            'product_name' => 'nullable|string|max:50',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'product_raw_id' => 'nullable|exists:product_raw,id',
            'from' => 'nullable|string|max:50',
            'cost' => 'nullable|numeric|min:0',
            'unit_type' => 'nullable|string|max:50',
            'lkp_status_sku' => 'nullable|integer|between:1,7',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $masterSku->update($request->only([
                'sku_code', 'product_name', 'supplier_id', 'product_raw_id', 'from', 'cost', 'unit_type', 'lkp_status_sku',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Master SKU updated successfully',
                'data' => $masterSku->fresh()->load('suppliers'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update master SKU', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $masterSku = MasterSku::find($id);

        if (!$masterSku) {
            return response()->json(['success' => false, 'message' => 'Master SKU not found'], 404);
        }

        try {
            $masterSku->delete();
            return response()->json(['success' => true, 'message' => 'Master SKU deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete master SKU', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $masterSku = MasterSku::find($id);

        if (!$masterSku) {
            return response()->json(['success' => false, 'message' => 'Master SKU not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'lkp_status_sku' => 'required|integer|between:1,7',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $masterSku->update(['lkp_status_sku' => $request->lkp_status_sku]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $masterSku->fresh()->load('suppliers'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update status', 'error' => $e->getMessage()], 500);
        }
    }

    public function statistics()
    {
        $totalSkus = MasterSku::count();
        $totalValue = MasterSku::sum(DB::raw('CAST(cost AS DECIMAL(10,2))'));
        $activeSkus = MasterSku::where('lkp_status_sku', 1)->count();

        $bySupplier = MasterSku::join('suppliers', 'master_sku.supplier_id', '=', 'suppliers.id')
            ->select('suppliers.id', 'suppliers.name', DB::raw('COUNT(master_sku.id) as count'))
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_skus' => $totalSkus,
                'active_skus' => $activeSkus,
                'total_value' => (float) $totalValue,
                'by_supplier' => $bySupplier,
            ],
        ]);
    }
}
