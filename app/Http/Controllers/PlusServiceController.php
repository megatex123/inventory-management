<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use App\Models\PlusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PlusServiceController extends Controller
{
    use FiltersSortsAndPaginates;

    const CATEGORIES = ['installation', 'upgrade', 'onsite', 'cable_mgmt', 'cleaning', 'thermal_paste', 'combo', 'distance_fee'];

    public function index(Request $request)
    {
        $query = PlusService::query();

        $this->applyEqualsFilter($query, $request, 'category', 'category');
        $this->applyLikeFilter($query, $request, 'search', 'name');

        $isActive = $request->input('is_active');
        if (is_scalar($isActive) && $isActive !== '') {
            $query->where('is_active', (bool) $isActive);
        }

        $this->resolveSortAndApply($query, $request, ['service_code', 'name', 'category', 'price', 'created_at'], 'created_at', 'id', [], 'desc');

        $perPage = $this->resolvePerPage($request, 15);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    public function search(Request $request)
    {
        $query = PlusService::query();

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        $results = $query->paginate($request->get('per_page', 10));

        return response()->json(['success' => true, 'data' => $results->items()]);
    }

    public function show($id)
    {
        $service = PlusService::find($id);

        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $service]);
    }

    public function edit($id)
    {
        $service = PlusService::find($id);

        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'plus_service' => $service,
                'categories' => self::CATEGORIES,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'category' => 'required|string|in:' . implode(',', self::CATEGORIES),
            'price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $nextId = PlusService::count() + 1;
            $serviceCode = 'PS-QVPL-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $service = PlusService::create([
                'service_code' => $serviceCode,
                'name' => $request->name,
                'category' => $request->category,
                'price' => $request->price,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Service created successfully',
                'data' => $service,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create service', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $service = PlusService::find($id);

        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'category' => 'required|string|in:' . implode(',', self::CATEGORIES),
            'price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $service->update([
                'name' => $request->name,
                'category' => $request->category,
                'price' => $request->price,
                'is_active' => $request->boolean('is_active'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service updated successfully',
                'data' => $service->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update service', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $service = PlusService::find($id);

        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }

        try {
            $service->delete();
            return response()->json(['success' => true, 'message' => 'Service deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete service', 'error' => $e->getMessage()], 500);
        }
    }

    public function statistics()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_services' => PlusService::count(),
                'active_services' => PlusService::where('is_active', true)->count(),
                'by_category' => PlusService::select('category', DB::raw('COUNT(*) as count'))->groupBy('category')->get(),
            ],
        ]);
    }
}
