<?php

namespace App\Http\Controllers;

use App\Models\ServeBek;
use App\Models\ServeData;
use App\Models\Serves;
use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ServeBekController extends Controller
{
    use FiltersSortsAndPaginates;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = ServeBek::with('serveData')->active();

            $this->applyEqualsFilter($query, $request, 'serve_data_id', 'serve_data_id');

            $qvseCid = $request->input('qvse_cid');
            if (is_scalar($qvseCid) && $qvseCid !== '') {
                $escaped = addcslashes((string) $qvseCid, '%_\\');
                $query->whereHas('serveData', function ($q) use ($escaped) {
                    $q->where('qvse_cid', 'LIKE', '%' . $escaped . '%');
                });
            }

            $dateFrom = $request->input('date_from');
            if (is_scalar($dateFrom) && $dateFrom !== '') {
                $query->where('date_start', '>=', $dateFrom);
            }
            $dateTo = $request->input('date_to');
            if (is_scalar($dateTo) && $dateTo !== '') {
                $query->where('date_start', '<=', $dateTo);
            }

            $this->resolveSortAndApply($query, $request, ['created_at', 'date_start'], 'created_at', 'id', [], 'desc');

            $perPage = $this->resolvePerPage($request, 15);
            $results = $query->paginate($perPage);

            $results->getCollection()->transform(function ($item) {
                return $this->formatServeBekItem($item);
            });

            return $this->paginatedResponse($results);
        } catch (\Exception $e) {
            Log::error('ServeBekController@index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ServeBek records.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Statistics for the Serve BEK dashboard — same shape/style as
     * ServeMpsController::getStatistics(), adapted for BEK's single-claim
     * perks (no promo code perk on Essential Kit, so the 4th card is
     * "available claims" instead of "available promo codes").
     */
    public function statistics()
    {
        try {
            $total = ServeBek::active()->count();

            $activeWarranty = ServeBek::active()
                ->where('one_year_assembly_warranty', true)
                ->where('date_start', '>=', now()->subYear()->format('Y-m-d'))
                ->count();
            $expiredWarranty = $total - $activeWarranty;

            $activeWarrantyPercentage = $total > 0 ? round(($activeWarranty / $total) * 100, 1) : 0;
            $expiredWarrantyPercentage = $total > 0 ? round(($expiredWarranty / $total) * 100, 1) : 0;

            $availableTroubleshootingClaims = ServeBek::active()
                ->where('one_free_onsite_troubleshooting_first_3_months', true)
                ->where('one_free_onsite_troubleshooting_claim_1', false)
                ->count();

            $availableCableManagementClaims = ServeBek::active()
                ->where('one_basic_cable_management_3_months', true)
                ->where('one_basic_cable_management_claim_1', false)
                ->count();

            $availableDustCleaningClaims = ServeBek::active()
                ->where('fifty_percent_off_dust_cleaning_first_year', true)
                ->where('fifty_percent_off_dust_cleaning_claim_1', false)
                ->count();

            $availableClaims = $availableTroubleshootingClaims + $availableCableManagementClaims + $availableDustCleaningClaims;

            return response()->json([
                'success' => true,
                'statistics' => [
                    'total_records' => $total,
                    'active_warranty' => $activeWarranty,
                    'expired_warranty' => $expiredWarranty,
                    'active_warranty_percentage' => $activeWarrantyPercentage,
                    'expired_warranty_percentage' => $expiredWarrantyPercentage,
                    'available_troubleshooting_claims' => $availableTroubleshootingClaims,
                    'available_cable_management_claims' => $availableCableManagementClaims,
                    'available_dust_cleaning_claims' => $availableDustCleaningClaims,
                    'available_claims' => $availableClaims,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('ServeBekController@statistics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics.',
                'statistics' => []
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'serve_data_id' => 'required|exists:serve_data,id',
            'date_start' => 'required|date',
            'one_year_assembly_warranty' => 'boolean',
            'one_free_onsite_troubleshooting_first_3_months' => 'boolean',
            'one_basic_cable_management_3_months' => 'boolean',
            'fifty_percent_off_dust_cleaning_first_year' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if serve_data_id already has a record
            $existingRecord = ServeBek::where('serve_data_id', $request->serve_data_id)
                ->active()
                ->first();

            if ($existingRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'A ServeBek record already exists for this serve data.'
                ], 409);
            }

            $serveBek = ServeBek::create($request->only([
                // 'serve_data_id',
                'date_start',
                'one_year_assembly_warranty',
                'one_free_onsite_troubleshooting_first_3_months',
                'one_basic_cable_management_3_months',
                'fifty_percent_off_dust_cleaning_first_year',
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $this->formatServeBekItem($serveBek->load('serveData')),
                'message' => 'ServeBek record created successfully.'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ServeBekController@store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ServeBek record.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $serveBek = ServeBek::with('serveData')
                ->active()
                ->find($id);

            if (!$serveBek) {
                return response()->json([
                    'success' => false,
                    'message' => 'ServeBek record not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatServeBekItem($serveBek),
                'message' => 'ServeBek record retrieved successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('ServeBekController@show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ServeBek record.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $serveBek = ServeBek::active()->find($id);

        if (!$serveBek) {
            return response()->json([
                'success' => false,
                'message' => 'ServeBek record not found.'
            ], 404);
        }

        $request['serve_data_id'] = $serveBek->serve_data_id;

        $validator = Validator::make($request->all(), [
            'date_start' => 'sometimes|date',
            'one_year_assembly_warranty' => 'sometimes|boolean',
            'one_free_onsite_troubleshooting_first_3_months' => 'sometimes|boolean',
            'one_free_onsite_troubleshooting_claim_1' => 'sometimes|boolean',
            'one_free_onsite_troubleshooting_claim_1_date' => 'sometimes|date|nullable',
            'one_basic_cable_management_3_months' => 'sometimes|boolean',
            'one_basic_cable_management_claim_1' => 'sometimes|boolean',
            'one_basic_cable_management_claim_1_date' => 'sometimes|date|nullable',
            'fifty_percent_off_dust_cleaning_first_year' => 'sometimes|boolean',
            'fifty_percent_off_dust_cleaning_claim_1' => 'sometimes|boolean',
            'fifty_percent_off_dust_cleaning_claim_1_date' => 'sometimes|date|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update claim dates only if claim is set to true
            if ($request->has('one_free_onsite_troubleshooting_claim_1') && $request->one_free_onsite_troubleshooting_claim_1) {
                $request->merge([
                    'one_free_onsite_troubleshooting_claim_1_date' => $request->one_free_onsite_troubleshooting_claim_1_date ?? now()
                ]);
            }

            if ($request->has('one_basic_cable_management_claim_1') && $request->one_basic_cable_management_claim_1) {
                $request->merge([
                    'one_basic_cable_management_claim_1_date' => $request->one_basic_cable_management_claim_1_date ?? now()
                ]);
            }

            if ($request->has('fifty_percent_off_dust_cleaning_claim_1') && $request->fifty_percent_off_dust_cleaning_claim_1) {
                $request->merge([
                    'fifty_percent_off_dust_cleaning_claim_1_date' => $request->fifty_percent_off_dust_cleaning_claim_1_date ?? now()
                ]);
            }

            $serveBek->update($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $this->formatServeBekItem($serveBek->load('serveData')),
                'message' => 'ServeBek record updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ServeBekController@update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ServeBek record.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $serveBek = ServeBek::active()->find($id);

        if (!$serveBek) {
            return response()->json([
                'success' => false,
                'message' => 'ServeBek record not found.'
            ], 404);
        }

        try {
            $serveBek->delete();

            return response()->json([
                'success' => true,
                'message' => 'ServeBek record deleted successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('ServeBekController@destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ServeBek record.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Restore a soft deleted record.
     */
    public function restore($id)
    {
        $serveBek = ServeBek::withTrashed()->find($id);

        if (!$serveBek) {
            return response()->json([
                'success' => false,
                'message' => 'ServeBek record not found.'
            ], 404);
        }

        try {
            $serveBek->restore();

            return response()->json([
                'success' => true,
                'data' => $this->formatServeBekItem($serveBek->load('serveData')),
                'message' => 'ServeBek record restored successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('ServeBekController@restore error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore ServeBek record.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get ServeBek by serve_data_id
     */
    public function getByServeDataId($serveDataId)
    {
        try {
            $serveBek = ServeBek::with('serveData')
                ->byServeData($serveDataId)
                ->active()
                ->first();

            if (!$serveBek) {
                return response()->json([
                    'success' => false,
                    'message' => 'No ServeBek record found for this serve data.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatServeBekItem($serveBek),
                'message' => 'ServeBek record retrieved successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('ServeBekController@getByServeDataId error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ServeBek record.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Resolve (or create) the ServeBek record for a given order, the same
     * way Craft Inspection resolves its record from an order id — lets the
     * order list jump straight to the right record instead of making staff
     * search for the QVSE CID manually. Only valid for Essential Kit orders
     * (lkp_serve_id = 1); the ServeData row itself is created by
     * OrderController::updateserve() when the order is approved, so this
     * 404s if the order hasn't been approved yet.
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

            if ((int) $serveData->lkp_serve_id !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order is not on the Essential Kit (BEK) tier.'
                ], 422);
            }

            $serveBek = ServeBek::byServeData($serveData->id)->active()->first();

            if (!$serveBek) {
                $serveType = Serves::find($serveData->lkp_serve_id);
                $serveTypeCode = $serveType ? strtoupper($serveType->code) : 'BEK';
                $serveBekNumber = str_pad(ServeBek::count() + 1, 4, '0', STR_PAD_LEFT);

                $serveBek = ServeBek::create([
                    'serve_bek_id' => "{$serveTypeCode}-{$serveBekNumber}",
                    'serve_data_id' => $serveData->id,
                    // Connect to QuiviServe's own start date rather than leaving it blank.
                    'date_start' => $serveData->start_serve_enabled && $serveData->start_serve_date
                        ? $serveData->start_serve_date->format('Y-m-d')
                        : null,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatServeBekItem($serveBek->load('serveData')),
                'message' => 'ServeBek record resolved successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('ServeBekController@getByOrder error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve ServeBek record.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Make a claim for a specific service
     */
    public function makeClaim(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'claim_type' => 'required|in:troubleshooting,cable_management,dust_cleaning',
            'claim_date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $serveBek = ServeBek::active()->find($id);

        if (!$serveBek) {
            return response()->json([
                'success' => false,
                'message' => 'ServeBek record not found.'
            ], 404);
        }

        // Check if claim is available
        if (!$serveBek->isClaimAvailable($request->claim_type)) {
            return response()->json([
                'success' => false,
                'message' => 'Claim not available or already used.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $claimDate = $request->claim_date ?? now();

            switch ($request->claim_type) {
                case 'troubleshooting':
                    $serveBek->update([
                        'one_free_onsite_troubleshooting_claim_1' => true,
                        'one_free_onsite_troubleshooting_claim_1_date' => $claimDate
                    ]);
                    break;

                case 'cable_management':
                    $serveBek->update([
                        'one_basic_cable_management_claim_1' => true,
                        'one_basic_cable_management_claim_1_date' => $claimDate
                    ]);
                    break;

                case 'dust_cleaning':
                    $serveBek->update([
                        'fifty_percent_off_dust_cleaning_claim_1' => true,
                        'fifty_percent_off_dust_cleaning_claim_1_date' => $claimDate
                    ]);
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $this->formatServeBekItem($serveBek->fresh()->load('serveData')),
                'message' => 'Claim made successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ServeBekController@makeClaim error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to make claim.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Format ServeBek item for response
     */
    private function formatServeBekItem($item)
    {
        return [
            'id' => $item->id,
            'serve_data_id' => $item->serveData ? $item->serveData->serve_id : null,
            'qvse_cid' => $item->qvse_cid,
            // Fall back to QuiviServe's own start date when this record's
            // own date_start was never seeded (e.g. rows created before
            // getByOrder()'s date_start seeding was added) -- matches
            // serve_bek/edit.vue's display-side backfill so the list and
            // edit pages agree on what a record's start date actually is.
            'date_start' => $item->date_start
                ? $item->date_start->format('Y-m-d')
                : (($item->serveData && $item->serveData->start_serve_enabled && $item->serveData->start_serve_date)
                    ? $item->serveData->start_serve_date->format('Y-m-d')
                    : null),
            'warranty' => [
                'one_year_assembly_warranty' => (bool) $item->one_year_assembly_warranty,
            ],
            'troubleshooting' => [
                'available' => (bool) $item->one_free_onsite_troubleshooting_first_3_months,
                'claimed' => (bool) $item->one_free_onsite_troubleshooting_claim_1,
                'claim_date' => $item->one_free_onsite_troubleshooting_claim_1_date ? $item->one_free_onsite_troubleshooting_claim_1_date->format('Y-m-d') : null,
            ],
            'cable_management' => [
                'available' => (bool) $item->one_basic_cable_management_3_months,
                'claimed' => (bool) $item->one_basic_cable_management_claim_1,
                'claim_date' => $item->one_basic_cable_management_claim_1_date ? $item->one_basic_cable_management_claim_1_date->format('Y-m-d') : null,
            ],
            'dust_cleaning' => [
                'available' => (bool) $item->fifty_percent_off_dust_cleaning_first_year,
                'claimed' => (bool) $item->fifty_percent_off_dust_cleaning_claim_1,
                'claim_date' => $item->fifty_percent_off_dust_cleaning_claim_1_date ? $item->fifty_percent_off_dust_cleaning_claim_1_date->format('Y-m-d') : null,
            ],
            'created_at' => $item->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $item->updated_at->format('Y-m-d H:i:s'),
            'serve_data' => $item->serveData ? [
                'id' => $item->serveData->id,
                'qvse_cid' => $item->serveData->qvse_cid,
                'start_serve_enabled' => (bool) $item->serveData->start_serve_enabled,
                'start_serve_date' => $item->serveData->start_serve_date ? $item->serveData->start_serve_date->format('Y-m-d') : null,
                // Add other serve_data fields as needed
            ] : null,
        ];
    }
}
