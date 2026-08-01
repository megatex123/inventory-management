<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use App\Models\InvMove;
use App\Models\MasterSku;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class InventoryMovementController extends Controller
{
    use FiltersSortsAndPaginates;

    public function index(Request $request)
    {
        $query = InvMove::with(['masterSku', 'destination', 'order']);

        $this->applyEqualsFilter($query, $request, 'destination_id', 'destination_id');
        $this->applyEqualsFilter($query, $request, 'master_sku_id', 'master_sku_id');
        $this->applyEqualsFilter($query, $request, 'order_id', 'order_id');
        $this->applyEqualsFilter($query, $request, 'type', 'type');

        $dateFrom = $request->input('date_from');
        if (is_scalar($dateFrom) && $dateFrom !== '') {
            $query->whereDate('date', '>=', $dateFrom);
        }

        $dateTo = $request->input('date_to');
        if (is_scalar($dateTo) && $dateTo !== '') {
            $query->whereDate('date', '<=', $dateTo);
        }

        $search = $request->input('search');
        if (is_scalar($search) && $search !== '') {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('movement_id', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('item_name', 'LIKE', '%' . $escaped . '%')
                    ->orWhereHas('masterSku', function ($q2) use ($escaped) {
                        $q2->where('sku_code', 'LIKE', '%' . $escaped . '%');
                    })
                    ->orWhereHas('destination', function ($q2) use ($escaped) {
                        $q2->where('description', 'LIKE', '%' . $escaped . '%');
                    })
                    ->orWhereHas('order', function ($q2) use ($escaped) {
                        $q2->where('order_id', 'LIKE', '%' . $escaped . '%');
                    });
            });
        }

        $this->resolveSortAndApply($query, $request, ['movement_id', 'date', 'type', 'quantity', 'unit_cost'], 'date', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    public function show($id)
    {
        $movement = InvMove::with(['masterSku', 'destination', 'order'])->find($id);

        if (!$movement) {
            return response()->json(['success' => false, 'message' => 'Movement not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $movement]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'sku_code' => 'required|string|max:50',
            'item_name' => 'nullable|string|max:191',
            'destination' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
            'quantity' => 'required|integer|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'order_id' => 'nullable|exists:order,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $masterSku = $this->findOrCreateMasterSku($request->sku_code, $request->item_name);
            $destination = $this->findOrCreateDestination($request->destination);

            $movement = InvMove::create([
                'movement_id' => $this->nextMovementId(),
                'date' => Carbon::parse($request->date),
                'master_sku_id' => $masterSku->id,
                'destination_id' => $destination->id,
                'order_id' => $request->order_id ?: null,
                'item_name' => $request->item_name ?: $masterSku->product_name,
                'type' => $request->type ?: 'Inventory',
                'quantity' => $request->quantity,
                'unit_cost' => $request->unit_cost,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Movement created successfully',
                'data' => $movement->load(['masterSku', 'destination', 'order']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create movement',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $movement = InvMove::find($id);

        if (!$movement) {
            return response()->json(['success' => false, 'message' => 'Movement not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'sku_code' => 'required|string|max:50',
            'item_name' => 'nullable|string|max:191',
            'destination' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
            'quantity' => 'required|integer|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'order_id' => 'nullable|exists:order,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $masterSku = $this->findOrCreateMasterSku($request->sku_code, $request->item_name);
            $destination = $this->findOrCreateDestination($request->destination);

            $movement->update([
                'date' => Carbon::parse($request->date),
                'master_sku_id' => $masterSku->id,
                'destination_id' => $destination->id,
                'order_id' => $request->order_id ?: null,
                'item_name' => $request->item_name ?: $masterSku->product_name,
                'type' => $request->type ?: 'Inventory',
                'quantity' => $request->quantity,
                'unit_cost' => $request->unit_cost,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Movement updated successfully',
                'data' => $movement->fresh()->load(['masterSku', 'destination', 'order']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update movement',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $movement = InvMove::find($id);

        if (!$movement) {
            return response()->json(['success' => false, 'message' => 'Movement not found'], 404);
        }

        $movement->delete();

        return response()->json(['success' => true, 'message' => 'Movement deleted successfully']);
    }

    public function statistics()
    {
        $total = InvMove::count();
        $totalQty = (int) InvMove::sum('quantity');
        $totalValue = (float) InvMove::selectRaw('SUM(quantity * unit_cost) as value')->value('value');

        $byType = InvMove::select('type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        $byDestination = InvMove::join('destination', 'inv_move.destination_id', '=', 'destination.id')
            ->select('destination.description')
            ->selectRaw('COUNT(*) as count, SUM(inv_move.quantity) as total_quantity')
            ->groupBy('destination.description')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_movements' => $total,
                'total_quantity' => $totalQty,
                'total_value' => round($totalValue, 2),
                'by_type' => $byType,
                'by_destination' => $byDestination,
            ],
        ]);
    }

    /**
     * Look up a MasterSku by code, creating a minimal record if it doesn't
     * exist yet — lets the movement form work as a single "type the SKU"
     * entry point instead of requiring SKUs to be pre-registered elsewhere.
     */
    private function findOrCreateMasterSku($skuCode, $itemName)
    {
        $masterSku = MasterSku::where('sku_code', $skuCode)->first();

        if ($masterSku) {
            return $masterSku;
        }

        return MasterSku::create([
            'sku_code' => $skuCode,
            'product_name' => $itemName,
            'lkp_status_sku' => 1,
        ]);
    }

    /**
     * Look up a Destination by its code/description, creating it if new.
     */
    private function findOrCreateDestination($description)
    {
        $destination = Destination::where('description', $description)->first();

        if ($destination) {
            return $destination;
        }

        return Destination::create([
            'description' => $description,
            'status' => 1,
        ]);
    }

    /**
     * The movement_id unique index still applies to soft-deleted rows, so
     * basing the next number on the current (non-trashed) max id can collide
     * with a code a deleted row already used. Scan movement_id itself
     * (withTrashed) and bump past whatever the highest number actually is.
     */
    private function nextMovementId()
    {
        $prefix = 'MVMT-';

        $last = InvMove::withTrashed()
            ->where('movement_id', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(movement_id, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();

        $lastNumber = $last ? (int) substr($last->movement_id, strlen($prefix)) : 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
