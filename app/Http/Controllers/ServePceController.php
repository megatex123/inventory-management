<?php

namespace App\Http\Controllers;

use App\Models\ServePce;
use App\Models\ServeData;
use App\Models\Serves;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ServePceController extends Controller
{
    /**
     * Resolve (or create) the ServePce record for a given order — same
     * order-row quick-launch pattern as ServeBek::getByOrder() /
     * ServeMps::getByOrder(). Only valid for Collector's Edition orders
     * (lkp_serve_id = 3); the ServeData row itself is created by
     * OrderController::updateserve() on order approval, so this 404s if
     * the order hasn't been approved yet.
     */
    public function getByOrder($orderId)
    {
        try {
            $serveData = ServeData::withTrashed()->where('order_id', $orderId)->first();

            if (!$serveData) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has no QuiviServe record yet — approve the order first.'
                ], 404);
            }

            if ((int) $serveData->lkp_serve_id !== 3) {
                return response()->json([
                    'success' => false,
                    'message' => "This order is not on the Collector's Edition (PCE) tier."
                ], 422);
            }

            $servePce = ServePce::where('serve_data_id', $serveData->id)->first();

            if (!$servePce) {
                $serveType = Serves::find($serveData->lkp_serve_id);
                $serveTypeCode = $serveType ? strtoupper($serveType->code) : 'PCE-2610';
                $servePceNumber = str_pad(ServePce::count() + 1, 4, '0', STR_PAD_LEFT);

                $servePce = ServePce::create([
                    'serve_pce_id' => "{$serveTypeCode}-{$servePceNumber}",
                    'serve_data_id' => $serveData->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => ['id' => $servePce->id, 'serve_data_id' => $servePce->serve_data_id],
                'message' => 'ServePce record resolved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve ServePce record: ' . $e->getMessage()
            ], 500);
        }
    }
    public function index(Request $request)
    {
        // Start the query with ServePce and join with ServeData
        $query = ServePce::with(['serveData.customer']); // Load customer through serveData

        // Apply filters if provided
        if ($request->has('qvse_cid') && !empty($request->qvse_cid)) {
            $query->whereHas('serveData', function($q) use ($request) {
                $q->where('qvse_cid', 'like', '%' . $request->qvse_cid . '%');
            });
        }

        // Filter by warranty status (active/expired)
        if ($request->has('warranty_status') && $request->warranty_status !== '') {
            // This will be handled in the frontend, but we can add date-based filtering
            if ($request->warranty_status === 'active') {
                $query->where('date_start', '>=', now()->subYears(3));
            } elseif ($request->warranty_status === 'expired') {
                $query->where('date_start', '<', now()->subYears(3));
            }
        }

        // Filter by promo status
        if ($request->has('promo_status') && $request->promo_status !== '') {
            if ($request->promo_status === 'available') {
                $query->whereNotNull('promo_code')
                    ->where('generate_code', 1)
                    ->where('promo_claim', 0);
            } elseif ($request->promo_status === 'claimed') {
                $query->where('promo_claim', 1);
            } elseif ($request->promo_status === 'generated') {
                $query->where('generate_code', 1)->where('promo_claim', 0);
            }
        }

        if ($request->has('promo_code') && $request->promo_code !== '') {
            $query->where('promo_code', 'like', '%' . $request->promo_code . '%');
        }

        if ($request->has('start_date_from') && !empty($request->start_date_from)) {
            $query->where('date_start', '>=', $request->start_date_from);
        }

        if ($request->has('start_date_to') && !empty($request->start_date_to)) {
            $query->where('date_start', '<=', $request->start_date_to);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $servePces = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform each item
        $transformedData = collect($servePces->items())->map(function ($item) {
            return $this->formatServePceItem($item);
        });

        return response()->json([
            'success' => true,
            'data' => $transformedData,
            'meta' => [
                'total' => $servePces->total(),
                'per_page' => $servePces->perPage(),
                'current_page' => $servePces->currentPage(),
                'last_page' => $servePces->lastPage(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $totalServesPce = ServePce::count();
        $nextId = $totalServesPce + 1;
        $serveNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
        $serve_pce_id = "PCE-2610-{$serveNumber}";

        $request->merge(['serve_pce_id' => $serve_pce_id]);

        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'serve_pce_id' => 'required',
                'serve_data_id' => 'required|exists:serve_data,id',
                'date_start' => 'required|date',
                'three_year_warranty' => 'nullable|string|max:255',
                'unlimited_troubleshooting' => 'nullable|string|max:255',
                'troubleshooting' => 'nullable|string|max:255',
                'cable_management' => 'nullable|string|max:50',
                'cable_management_claim1' => 'nullable|in:0,1,Yes,No',
                'cable_management_claim1_date' => 'nullable|date|after_or_equal:date_start',
                'cable_management_claim2' => 'nullable|in:0,1,Yes,No',
                'cable_management_claim2_date' => 'nullable|date',
                'cable_management_claim3' => 'nullable|in:0,1,Yes,No',
                'cable_management_claim3_date' => 'nullable|date',
                'cable_management_claim4' => 'nullable|in:0,1,Yes,No',
                'cable_management_claim4_date' => 'nullable|date',
                'annual_dust_cleaning' => 'nullable|string|max:100',
                'annual_dust_cleaning_year1' => 'nullable|in:0,1,Yes,No',
                'claim_date_year1' => 'nullable|date|after_or_equal:date_start',
                'annual_dust_cleaning_year2' => 'nullable|in:0,1,Yes,No',
                'claim_date_year2' => 'nullable|date',
                'annual_dust_cleaning_year3' => 'nullable|in:0,1,Yes,No',
                'claim_date_year3' => 'nullable|date',
                'dust_cleaning_50_description' => 'nullable|string|max:100',
                'dust_cleaning_50_year4' => 'nullable|in:0,1,Yes,No',
                'dust_cleaning_50_claim_date_year4' => 'nullable|date',
                'dust_cleaning_50_year5' => 'nullable|in:0,1,Yes,No',
                'dust_cleaning_50_claim_date_year5' => 'nullable|date',
                'dust_cleaning_50_year6' => 'nullable|in:0,1,Yes,No',
                'dust_cleaning_50_claim_date_year6' => 'nullable|date',
                'dust_cleaning_50_year7' => 'nullable|in:0,1,Yes,No',
                'dust_cleaning_50_claim_date_year7' => 'nullable|date',
                'upgrade_service_50_description' => 'nullable|string|max:100',
                'upgrade_service_50_year1' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_50_claim_date_year1' => 'nullable|date',
                'upgrade_service_50_year2' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_50_claim_date_year2' => 'nullable|date',
                'upgrade_service_50_year3' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_50_claim_date_year3' => 'nullable|date',
                'upgrade_service_30_description' => 'nullable|string|max:100',
                'upgrade_service_30_year4' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_30_claim_date_year4' => 'nullable|date',
                'upgrade_service_30_year5' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_30_claim_date_year5' => 'nullable|date',
                'upgrade_service_30_year6' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_30_claim_date_year6' => 'nullable|date',
                'upgrade_service_30_year7' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_30_claim_date_year7' => 'nullable|date',
                'promo_code' => 'nullable|string|max:50|unique:serve_pce,promo_code',
                'generate_code' => 'nullable|in:0,1',
                'promo_claim' => 'nullable|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if serve_data_id already has a Serve PCE record
            $existingRecord = ServePce::where('serve_data_id', $request->serve_data_id)->first();
            if ($existingRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'A Serve PCE record already exists for this QVSE CID'
                ], 409); // 409 Conflict
            }

            // Check if the serve_data is Collector's Edition
            $serveData = ServeData::find($request->serve_data_id);
            if (!$serveData || $serveData->lkp_serve_id != 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only Collector\'s Edition QVSE CIDs can have Serve PCE records'
                ], 422);
            }

            // Prepare data for creation
            $data = $validator->validated();

            // Convert checkbox values to proper format
            $checkboxFields = [
                'cable_management_claim1', 'cable_management_claim2', 'cable_management_claim3', 'cable_management_claim4',
                'annual_dust_cleaning_year1', 'annual_dust_cleaning_year2', 'annual_dust_cleaning_year3',
                'dust_cleaning_50_year4', 'dust_cleaning_50_year5', 'dust_cleaning_50_year6', 'dust_cleaning_50_year7',
                'upgrade_service_50_year1', 'upgrade_service_50_year2', 'upgrade_service_50_year3',
                'upgrade_service_30_year4', 'upgrade_service_30_year5', 'upgrade_service_30_year6', 'upgrade_service_30_year7',
                'generate_code', 'promo_claim'
            ];

            foreach ($checkboxFields as $field) {
                if (isset($data[$field])) {
                    if ($data[$field] === 'Yes') {
                        $data[$field] = '1';
                    } elseif ($data[$field] === 'No') {
                        $data[$field] = '0';
                    }
                }
            }

            // Set default values if not provided
            $data['three_year_warranty'] = $data['three_year_warranty'] ?? 'Yes';
            $data['unlimited_troubleshooting'] = $data['unlimited_troubleshooting'] ?? 'Yes';
            $data['troubleshooting'] = $data['troubleshooting'] ?? 'Yes';
            $data['cable_management'] = $data['cable_management'] ?? 'Premium Cable Management';
            $data['annual_dust_cleaning'] = $data['annual_dust_cleaning'] ?? 'Free Annual Deep Cleaning';
            $data['dust_cleaning_50_description'] = $data['dust_cleaning_50_description'] ?? '50% Off Annual Dust Cleaning';
            $data['upgrade_service_50_description'] = $data['upgrade_service_50_description'] ?? '50% Off Annual Upgrade Service';
            $data['upgrade_service_30_description'] = $data['upgrade_service_30_description'] ?? '30% Off Annual Upgrade Service';

            $servePce = ServePce::create($data);

            DB::commit();

            $formattedItem = $this->formatServePceItem($servePce->load('serveData.customer'));

            return response()->json([
                'success' => true,
                'data' => $formattedItem,
                'message' => 'Serve PCE record created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Serve PCE record',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $servePce = ServePce::with(['serveData'])->find($id);
            $formattedItem = $this->formatServePceItem($servePce);

            return response()->json([
                'success' => true,
                'data' => $formattedItem
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Serve PCE record not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $servePce = ServePce::findOrFail($id);

            $validator = Validator::make($request->all(), [
                // 'serve_data_id' => 'sometimes|required|exists:serve_data,id',
                'date_start' => 'sometimes|required|date',
                'three_year_warranty' => 'nullable|string|max:255',
                'unlimited_troubleshooting' => 'nullable|string|max:255',
                'troubleshooting' => 'nullable|string|max:255',
                'cable_management' => 'nullable|string|max:50',
                'cable_management_claim1' => 'nullable|in:0,1,Yes,No',
                'cable_management_claim1_date' => 'nullable|date',
                'cable_management_claim2' => 'nullable|in:0,1,Yes,No',
                'cable_management_claim2_date' => 'nullable|date',
                'cable_management_claim3' => 'nullable|in:0,1,Yes,No',
                'cable_management_claim3_date' => 'nullable|date',
                'cable_management_claim4' => 'nullable|in:0,1,Yes,No',
                'cable_management_claim4_date' => 'nullable|date',
                'annual_dust_cleaning' => 'nullable|string|max:100',
                'annual_dust_cleaning_year1' => 'nullable|in:0,1,Yes,No',
                'claim_date_year1' => 'nullable|date',
                'annual_dust_cleaning_year2' => 'nullable|in:0,1,Yes,No',
                'claim_date_year2' => 'nullable|date',
                'annual_dust_cleaning_year3' => 'nullable|in:0,1,Yes,No',
                'claim_date_year3' => 'nullable|date',
                'dust_cleaning_50_description' => 'nullable|string|max:100',
                'dust_cleaning_50_year4' => 'nullable|in:0,1,Yes,No',
                'dust_cleaning_50_claim_date_year4' => 'nullable|date',
                'dust_cleaning_50_year5' => 'nullable|in:0,1,Yes,No',
                'dust_cleaning_50_claim_date_year5' => 'nullable|date',
                'dust_cleaning_50_year6' => 'nullable|in:0,1,Yes,No',
                'dust_cleaning_50_claim_date_year6' => 'nullable|date',
                'dust_cleaning_50_year7' => 'nullable|in:0,1,Yes,No',
                'dust_cleaning_50_claim_date_year7' => 'nullable|date',
                'upgrade_service_50_description' => 'nullable|string|max:100',
                'upgrade_service_50_year1' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_50_claim_date_year1' => 'nullable|date',
                'upgrade_service_50_year2' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_50_claim_date_year2' => 'nullable|date',
                'upgrade_service_50_year3' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_50_claim_date_year3' => 'nullable|date',
                'upgrade_service_30_description' => 'nullable|string|max:100',
                'upgrade_service_30_year4' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_30_claim_date_year4' => 'nullable|date',
                'upgrade_service_30_year5' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_30_claim_date_year5' => 'nullable|date',
                'upgrade_service_30_year6' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_30_claim_date_year6' => 'nullable|date',
                'upgrade_service_30_year7' => 'nullable|in:0,1,Yes,No',
                'upgrade_service_30_claim_date_year7' => 'nullable|date',
                'promo_code' => 'nullable|string|max:50|unique:serve_pce,promo_code,' . $id,
                'generate_code' => 'nullable|in:0,1',
                'promo_claim' => 'nullable|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Prepare data for update
            $data = $validator->validated();

            // Convert checkbox values to proper format
            $checkboxFields = [
                'cable_management_claim1', 'cable_management_claim2', 'cable_management_claim3', 'cable_management_claim4',
                'annual_dust_cleaning_year1', 'annual_dust_cleaning_year2', 'annual_dust_cleaning_year3',
                'dust_cleaning_50_year4', 'dust_cleaning_50_year5', 'dust_cleaning_50_year6', 'dust_cleaning_50_year7',
                'upgrade_service_50_year1', 'upgrade_service_50_year2', 'upgrade_service_50_year3',
                'upgrade_service_30_year4', 'upgrade_service_30_year5', 'upgrade_service_30_year6', 'upgrade_service_30_year7',
                'generate_code', 'promo_claim'
            ];

            foreach ($checkboxFields as $field) {
                if (isset($data[$field])) {
                    if ($data[$field] === 'Yes') {
                        $data[$field] = '1';
                    } elseif ($data[$field] === 'No') {
                        $data[$field] = '0';
                    }
                }
            }

            $servePce->update($data);

            DB::commit();

            $formattedItem = $this->formatServePceItem($servePce->fresh()->load('serveData.customer'));

            return response()->json([
                'success' => true,
                'data' => $formattedItem,
                'message' => 'Serve PCE record updated successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Serve PCE record not found'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Serve PCE record',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $servePce = ServePce::findOrFail($id);
            $servePce->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Serve PCE record deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Serve PCE record not found'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Serve PCE record',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get statistics for Serve PCE records
     */
    public function statistics()
    {
        $total = ServePce::count();

        // Calculate active warranty (within 3 years from date_start)
        $activeWarranty = ServePce::where('date_start', '>=', now()->subYears(3))->count();
        $expiredWarranty = $total - $activeWarranty;

        $activePercentage = $total > 0 ? round(($activeWarranty / $total) * 100, 2) : 0;
        $expiredPercentage = $total > 0 ? round(($expiredWarranty / $total) * 100, 2) : 0;

        // Count available promo codes (generated but not claimed)
        $availablePromoCodes = ServePce::whereNotNull('promo_code')
            ->where('generate_code', 1)
            ->where('promo_claim', 0)
            ->distinct('promo_code')
            ->count('promo_code');

        // Count claimed promo codes
        $claimedPromoCodes = ServePce::where('promo_claim', 1)->count();

        // Count records with troubleshooting
        $withTroubleshooting = ServePce::where('troubleshooting', 'Yes')->count();
        $withUnlimitedTroubleshooting = ServePce::where('unlimited_troubleshooting', 'Yes')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_records' => $total,
                'active_warranty' => $activeWarranty,
                'expired_warranty' => $expiredWarranty,
                'available_promo_codes' => $availablePromoCodes,
                'claimed_promo_codes' => $claimedPromoCodes,
                'with_troubleshooting' => $withTroubleshooting,
                'with_unlimited_troubleshooting' => $withUnlimitedTroubleshooting,
                'active_warranty_percentage' => $activePercentage,
                'expired_warranty_percentage' => $expiredPercentage
            ]
        ]);
    }

    /**
     * Format ServePce item to include qvse_cid from the relationship
     */
    private function formatServePceItem($servePce)
    {
        if (!$servePce) {
            return null;
        }

        $item = $servePce->toArray();

        // Add qvse_cid and customer info from the relationship
        if ($servePce->relationLoaded('serveData') && $servePce->serveData) {
            $item['qvse_cid'] = $servePce->serveData->qvse_cid;
            $item['serve_data'] = $servePce->serveData;
            $item['serve_data_id'] = $servePce->serveData->serve_id;

            // Add customer info if loaded
            if ($servePce->serveData->relationLoaded('customer') && $servePce->serveData->customer) {
                $item['customer'] = [
                    'id' => $servePce->serveData->customer->id,
                    'full_name' => $servePce->serveData->customer->full_name,
                    'email' => $servePce->serveData->customer->email,
                    'phone' => $servePce->serveData->customer->phone
                ];
            }
        } else {
            $item['qvse_cid'] = null;
            $item['serve_data'] = null;
            $item['customer'] = null;
        }

        return $item;
    }

    public function search(Request $request)
    {
        $query = ServeData::with('customer', 'lkpServe');

        // Filter for Collector's Edition (lkp_serve_id = 3)
        $query->where('lkp_serve_id', 3);

        if ($request->has('qvse_cid') && !empty($request->qvse_cid)) {
            $query->where('qvse_cid', 'like', '%' . $request->qvse_cid . '%');
        }

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('qvse_cid', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('customer', function($q) use ($searchTerm) {
                      $q->where('full_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('email', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        $perPage = $request->get('per_page', 10);
        $serveData = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $serveData->items(),
            'meta' => [
                'total' => $serveData->total(),
                'per_page' => $serveData->perPage(),
                'current_page' => $serveData->currentPage(),
                'last_page' => $serveData->lastPage(),
            ]
        ]);
    }
}
