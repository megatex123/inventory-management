<?php

namespace App\Http\Controllers;

use App\Models\ThreadBomHeader;
use App\Models\ThreadBomLine;
use App\Models\MasterSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ThreadBomController extends Controller
{
    const CABLE_TYPES = ['24pin', '8eps', '8pcie', '12v2x6pcie'];

    public function index(Request $request)
    {
        $query = ThreadBomHeader::with(['lines']);

        if ($request->filled('psu_brand')) {
            $query->where('psu_brand', $request->psu_brand);
        }

        if ($request->filled('cable_type')) {
            $query->where('cable_type', $request->cable_type);
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
        $query = ThreadBomHeader::with(['lines']);

        if ($request->filled('search')) {
            $query->where('psu_brand', 'LIKE', "%{$request->search}%");
        }

        $results = $query->paginate($request->get('per_page', 10));

        return response()->json(['success' => true, 'data' => $results->items()]);
    }

    /**
     * Resolve the component list + total cost for a given
     * brand/cable-type/colour combination, without creating anything.
     * Used by the QuiviThread order form to preview a line before adding it.
     */
    public function resolve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'psu_brand' => 'required|string',
            'cable_type' => 'required|string|in:' . implode(',', self::CABLE_TYPES),
            'colour_variant' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $query = ThreadBomHeader::with(['lines'])
            ->where('psu_brand', $request->psu_brand)
            ->where('cable_type', $request->cable_type);

        if ($request->filled('colour_variant')) {
            $query->where(function ($q) use ($request) {
                $q->where('colour_variant', $request->colour_variant)
                    ->orWhere('colour_variant', 'Not');
            });
        }

        $headers = $query->get();

        if ($headers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No BOM found for this brand/cable type/colour combination',
            ], 404);
        }

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

        return response()->json([
            'success' => true,
            'data' => [
                'components' => $components,
                'total_cost' => (float) $totalCost,
            ],
        ]);
    }

    public function show($id)
    {
        $header = ThreadBomHeader::with(['lines.masterSku'])->find($id);

        if (!$header) {
            return response()->json(['success' => false, 'message' => 'BOM header not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $header]);
    }

    public function edit($id)
    {
        $header = ThreadBomHeader::with(['lines'])->find($id);

        if (!$header) {
            return response()->json(['success' => false, 'message' => 'BOM header not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'thread_bom_header' => $header,
                'master_skus' => MasterSku::orderBy('product_name')->get(),
                'cable_types' => self::CABLE_TYPES,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'psu_brand' => 'required|string|max:100',
            'cable_type' => 'required|string|in:' . implode(',', self::CABLE_TYPES),
            'colour_variant' => 'nullable|string|max:50',
            'is_default' => 'nullable|boolean',
            'lines' => 'required|array|min:1',
            'lines.*.sku_code' => 'required|string|exists:master_sku,sku_code',
            'lines.*.item_name' => 'required|string|max:191',
            'lines.*.qty_per_cable' => 'required|integer|min:1',
            'lines.*.unit_cost' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $header = ThreadBomHeader::create([
                'psu_brand' => $request->psu_brand,
                'cable_type' => $request->cable_type,
                'colour_variant' => $request->colour_variant,
                'is_default' => $request->boolean('is_default'),
            ]);

            foreach ($request->lines as $line) {
                ThreadBomLine::create([
                    'thread_bom_header_id' => $header->id,
                    'sku_code' => $line['sku_code'],
                    'item_name' => $line['item_name'],
                    'qty_per_cable' => $line['qty_per_cable'],
                    'unit_cost' => $line['unit_cost'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'BOM created successfully',
                'data' => $header->load('lines'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create BOM', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $header = ThreadBomHeader::find($id);

        if (!$header) {
            return response()->json(['success' => false, 'message' => 'BOM header not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'psu_brand' => 'required|string|max:100',
            'cable_type' => 'required|string|in:' . implode(',', self::CABLE_TYPES),
            'colour_variant' => 'nullable|string|max:50',
            'is_default' => 'nullable|boolean',
            'lines' => 'required|array|min:1',
            'lines.*.sku_code' => 'required|string|exists:master_sku,sku_code',
            'lines.*.item_name' => 'required|string|max:191',
            'lines.*.qty_per_cable' => 'required|integer|min:1',
            'lines.*.unit_cost' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $header->update($request->only(['psu_brand', 'cable_type', 'colour_variant']));
            $header->is_default = $request->boolean('is_default');
            $header->save();

            $header->lines()->delete();
            foreach ($request->lines as $line) {
                ThreadBomLine::create([
                    'thread_bom_header_id' => $header->id,
                    'sku_code' => $line['sku_code'],
                    'item_name' => $line['item_name'],
                    'qty_per_cable' => $line['qty_per_cable'],
                    'unit_cost' => $line['unit_cost'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'BOM updated successfully',
                'data' => $header->fresh()->load('lines'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update BOM', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $header = ThreadBomHeader::find($id);

        if (!$header) {
            return response()->json(['success' => false, 'message' => 'BOM header not found'], 404);
        }

        try {
            $header->delete();
            return response()->json(['success' => true, 'message' => 'BOM deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete BOM', 'error' => $e->getMessage()], 500);
        }
    }

    public function statistics()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_boms' => ThreadBomHeader::count(),
                'by_brand' => ThreadBomHeader::select('psu_brand', DB::raw('COUNT(*) as count'))->groupBy('psu_brand')->get(),
                'by_cable_type' => ThreadBomHeader::select('cable_type', DB::raw('COUNT(*) as count'))->groupBy('cable_type')->get(),
            ],
        ]);
    }
}
