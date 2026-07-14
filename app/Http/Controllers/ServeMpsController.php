<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ServeMps;
use App\Models\ServeData;
use App\Models\Serves;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServeMpsController extends Controller
{
    /**
     * Display a listing of the resource with filters and statistics
     */
    public function index(Request $request)
    {
        try {
            // Start the query with ServeMps
            $query = ServeMps::query();

            // Apply filters
            if ($request->has('qvse_cid') && !empty($request->qvse_cid)) {
                $query->where('qvse_cid', 'like', '%' . $request->qvse_cid . '%');
            }

            if ($request->has('date_start_from') && !empty($request->date_start_from)) {
                $query->where('date_start', '>=', $request->date_start_from);
            }

            if ($request->has('date_start_to') && !empty($request->date_start_to)) {
                $query->where('date_start', '<=', $request->date_start_to);
            }

            if ($request->has('has_claims') && $request->has_claims !== '') {
                if ($request->has_claims == '1') {
                    $query->where(function($q) {
                        $q->where('two_free_onsite_troubleshooting_claim_1', true)
                          ->orWhere('two_free_onsite_troubleshooting_claim_2', true)
                          ->orWhere('two_advance_cable_management_claim_1', true)
                          ->orWhere('two_advance_cable_management_claim_2', true)
                          ->orWhere('one_free_dust_cleaning_claim', true);
                    });
                } elseif ($request->has_claims == '0') {
                    $query->where(function($q) {
                        $q->where('two_free_onsite_troubleshooting_claim_1', false)
                          ->where('two_free_onsite_troubleshooting_claim_2', false)
                          ->where('two_advance_cable_management_claim_1', false)
                          ->where('two_advance_cable_management_claim_2', false)
                          ->where('one_free_dust_cleaning_claim', false);
                    });
                }
            }

            // Filter by warranty status
            if ($request->has('warranty_status') && !empty($request->warranty_status)) {
                if ($request->warranty_status == 'active') {
                    $query->where('two_year_assembly_warranty', true)
                          ->where('date_start', '>=', now()->subYears(2)->format('Y-m-d'));
                } elseif ($request->warranty_status == 'expired') {
                    $query->where(function($q) {
                        $q->where('two_year_assembly_warranty', false)
                          ->orWhere('date_start', '<', now()->subYears(2)->format('Y-m-d'));
                    });
                }
            }

            // Filter by promo code status
            if ($request->has('promo_status') && !empty($request->promo_status)) {
                if ($request->promo_status == 'generated') {
                    $query->where('generate_code', true)
                          ->where('rm100_promo_code_claim', false);
                } elseif ($request->promo_status == 'claimed') {
                    $query->where('rm100_promo_code_claim', true);
                } elseif ($request->promo_status == 'available') {
                    $query->whereNotNull('rm100_promo_code_next_build')
                          ->where('rm100_promo_code_claim', false);
                } elseif ($request->promo_status == 'not_generated') {
                    $query->where('generate_code', false);
                }
            }

            // Search by promo code
            if ($request->has('promo_code') && !empty($request->promo_code)) {
                $query->where('rm100_promo_code_next_build', 'like', '%' . $request->promo_code . '%');
            }

            // Order by latest
            $query->orderBy('created_at', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 10);
            $page = $request->get('page', 1);

            // Get paginated results
            $serveMps = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform the data to include customer information
            $transformedData = [];
            foreach ($serveMps->items() as $item) {
                $customerInfo = null;
                if ($item->serve_data_id) {
                    $serveData = ServeData::with('customer')->find($item->serve_data_id);
                    if ($serveData && $serveData->customer) {
                        $customerInfo = [
                            'full_name' => $serveData->customer->full_name ?? null,
                            'email' => $serveData->customer->email ?? null,
                            'phone' => $serveData->customer->phone ?? null,
                        ];
                    }
                }

                $transformedData[] = [
                    'id' => $item->id,
                    'serve_data_id' => $item->serve_data_id,
                    'qvse_cid' => $item->qvse_cid,
                    'date_start' => $item->date_start ? $item->date_start->format('Y-m-d') : null,
                    'two_year_assembly_warranty' => (bool)$item->two_year_assembly_warranty,
                    'two_free_onsite_troubleshooting_first_6_months' => (bool)$item->two_free_onsite_troubleshooting_first_6_months,
                    'two_free_onsite_troubleshooting_claim_1' => (bool)$item->two_free_onsite_troubleshooting_claim_1,
                    'two_free_onsite_troubleshooting_claim_1_date' => $item->two_free_onsite_troubleshooting_claim_1_date ? $item->two_free_onsite_troubleshooting_claim_1_date->format('Y-m-d') : null,
                    'two_free_onsite_troubleshooting_claim_2' => (bool)$item->two_free_onsite_troubleshooting_claim_2,
                    'two_free_onsite_troubleshooting_claim_2_date' => $item->two_free_onsite_troubleshooting_claim_2_date ? $item->two_free_onsite_troubleshooting_claim_2_date->format('Y-m-d') : null,
                    'two_advance_cable_management_first_year' => (bool)$item->two_advance_cable_management_first_year,
                    'two_advance_cable_management_claim_1' => (bool)$item->two_advance_cable_management_claim_1,
                    'two_advance_cable_management_claim_1_date' => $item->two_advance_cable_management_claim_1_date ? $item->two_advance_cable_management_claim_1_date->format('Y-m-d') : null,
                    'two_advance_cable_management_claim_2' => (bool)$item->two_advance_cable_management_claim_2,
                    'two_advance_cable_management_claim_2_date' => $item->two_advance_cable_management_claim_2_date ? $item->two_advance_cable_management_claim_2_date->format('Y-m-d') : null,
                    'one_free_dust_cleaning_first_year' => (bool)$item->one_free_dust_cleaning_first_year,
                    'one_free_dust_cleaning_claim' => (bool)$item->one_free_dust_cleaning_claim,
                    'one_free_dust_cleaning_claim_date' => $item->one_free_dust_cleaning_claim_date ? $item->one_free_dust_cleaning_claim_date->format('Y-m-d') : null,
                    'fifty_percent_off_dust_cleaning_second_year' => (bool)$item->fifty_percent_off_dust_cleaning_second_year,
                    'fifty_percent_off_dust_cleaning_claim_date' => $item->fifty_percent_off_dust_cleaning_claim_date ? $item->fifty_percent_off_dust_cleaning_claim_date->format('Y-m-d') : null,
                    'thirty_percent_off_labour_fees_upgrade_first_year' => (bool)$item->thirty_percent_off_labour_fees_upgrade_first_year,
                    'thirty_percent_off_labour_fees_claim_date' => $item->thirty_percent_off_labour_fees_claim_date ? $item->thirty_percent_off_labour_fees_claim_date->format('Y-m-d') : null,
                    'thirty_percent_off_dust_cleaning' => (bool)$item->thirty_percent_off_dust_cleaning,
                    'thirty_percent_off_dust_cleaning_claim_date' => $item->thirty_percent_off_dust_cleaning_claim_date ? $item->thirty_percent_off_dust_cleaning_claim_date->format('Y-m-d') : null,
                    'rm100_promo_code_next_build' => $item->rm100_promo_code_next_build,
                    'generate_code' => (bool)$item->generate_code,
                    'rm100_promo_code_claim' => (bool)$item->rm100_promo_code_claim,
                    'notes' => $item->notes,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'deleted_at' => $item->deleted_at,
                    'customer' => $customerInfo,
                ];
            }

            // Calculate statistics for dashboard
            $total = ServeMps::count();
            $activeWarranty = ServeMps::where('two_year_assembly_warranty', true)
                ->where('date_start', '>=', now()->subYears(2)->format('Y-m-d'))
                ->count();
            $expiredWarranty = ServeMps::where(function($q) {
                $q->where('two_year_assembly_warranty', false)
                  ->orWhere('date_start', '<', now()->subYears(2)->format('Y-m-d'));
            })->count();
            $availablePromoCodes = ServeMps::whereNotNull('rm100_promo_code_next_build') // UPDATED FIELD
                ->where('generate_code', true)
                ->where('rm100_promo_code_claim', false)
                ->count();

            // Calculate percentages
            $activeWarrantyPercentage = $total > 0 ? round(($activeWarranty / $total) * 100, 1) : 0;
            $expiredWarrantyPercentage = $total > 0 ? round(($expiredWarranty / $total) * 100, 1) : 0;

            // Additional statistics
            $availableTroubleshootingClaims = ServeMps::where(function($q) {
                $q->where('two_free_onsite_troubleshooting_claim_1', false)
                  ->orWhere('two_free_onsite_troubleshooting_claim_2', false);
            })->count();

            $availableCableManagementClaims = ServeMps::where(function($q) {
                $q->where('two_advance_cable_management_claim_1', false)
                  ->orWhere('two_advance_cable_management_claim_2', false);
            })->count();

            $availableDustCleaningClaims = ServeMps::where('one_free_dust_cleaning_claim', false)->count();

            // Calculate claim rate using manual calculation
            $usedClaims = 0;
            $allRecords = ServeMps::all();
            foreach ($allRecords as $record) {
                if ($record->two_free_onsite_troubleshooting_claim_1) $usedClaims++;
                if ($record->two_free_onsite_troubleshooting_claim_2) $usedClaims++;
                if ($record->two_advance_cable_management_claim_1) $usedClaims++;
                if ($record->two_advance_cable_management_claim_2) $usedClaims++;
                if ($record->one_free_dust_cleaning_claim) $usedClaims++;
                if ($record->rm100_promo_code_claim) $usedClaims++;
            }

            $totalPossibleClaims = $total * 6; // 2 troubleshooting + 2 cable + 1 dust + 1 promo
            $claimRate = $totalPossibleClaims > 0 ? round(($usedClaims / $totalPossibleClaims) * 100, 1) : 0;

            // Calculate from/to for pagination
            $from = ($serveMps->currentPage() - 1) * $serveMps->perPage() + 1;
            $to = min($serveMps->currentPage() * $serveMps->perPage(), $serveMps->total());

            return response()->json([
                'success' => true,
                'message' => 'Serve MPS data retrieved successfully',
                'data' => $transformedData,
                'meta' => [
                    'total' => $serveMps->total(),
                    'per_page' => $serveMps->perPage(),
                    'current_page' => $serveMps->currentPage(),
                    'last_page' => $serveMps->lastPage(),
                    'from' => $from,
                    'to' => $to,
                ],
                'statistics' => [
                    'total_records' => $total,
                    'active_warranty' => $activeWarranty,
                    'expired_warranty' => $expiredWarranty,
                    'available_promo_codes' => $availablePromoCodes,
                    'active_warranty_percentage' => $activeWarrantyPercentage,
                    'expired_warranty_percentage' => $expiredWarrantyPercentage,
                    'available_troubleshooting_claims' => $availableTroubleshootingClaims,
                    'available_cable_management_claims' => $availableCableManagementClaims,
                    'available_dust_cleaning_claims' => $availableDustCleaningClaims,
                    'claim_rate' => $claimRate,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving Serve MPS data: ' . $e->getMessage(),
                'data' => [],
                'meta' => [],
                'statistics' => []
            ], 500);
        }
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics()
    {
        try {
            $total = ServeMps::count();
            $activeWarranty = ServeMps::where('two_year_assembly_warranty', true)
                ->where('date_start', '>=', now()->subYears(2)->format('Y-m-d'))
                ->count();
            $expiredWarranty = ServeMps::where(function($q) {
                $q->where('two_year_assembly_warranty', false)
                  ->orWhere('date_start', '<', now()->subYears(2)->format('Y-m-d'));
            })->count();
            $availablePromoCodes = ServeMps::whereNotNull('rm100_promo_code_next_build') // UPDATED FIELD
                ->where('generate_code', true)
                ->where('rm100_promo_code_claim', false)
                ->count();

            // Calculate percentages
            $activeWarrantyPercentage = $total > 0 ? round(($activeWarranty / $total) * 100, 1) : 0;
            $expiredWarrantyPercentage = $total > 0 ? round(($expiredWarranty / $total) * 100, 1) : 0;

            // Additional statistics
            $availableTroubleshootingClaims = ServeMps::where(function($q) {
                $q->where('two_free_onsite_troubleshooting_claim_1', false)
                  ->orWhere('two_free_onsite_troubleshooting_claim_2', false);
            })->count();

            $availableCableManagementClaims = ServeMps::where(function($q) {
                $q->where('two_advance_cable_management_claim_1', false)
                  ->orWhere('two_advance_cable_management_claim_2', false);
            })->count();

            $availableDustCleaningClaims = ServeMps::where('one_free_dust_cleaning_claim', false)->count();

            // Calculate claim rate
            $usedClaims = 0;
            $allRecords = ServeMps::all();
            foreach ($allRecords as $record) {
                if ($record->two_free_onsite_troubleshooting_claim_1) $usedClaims++;
                if ($record->two_free_onsite_troubleshooting_claim_2) $usedClaims++;
                if ($record->two_advance_cable_management_claim_1) $usedClaims++;
                if ($record->two_advance_cable_management_claim_2) $usedClaims++;
                if ($record->one_free_dust_cleaning_claim) $usedClaims++;
                if ($record->rm100_promo_code_claim) $usedClaims++;
            }

            $totalPossibleClaims = $total * 6;
            $claimRate = $totalPossibleClaims > 0 ? round(($usedClaims / $totalPossibleClaims) * 100, 1) : 0;

            return response()->json([
                'success' => true,
                'statistics' => [
                    'total_records' => $total,
                    'active_warranty' => $activeWarranty,
                    'expired_warranty' => $expiredWarranty,
                    'available_promo_codes' => $availablePromoCodes,
                    'active_warranty_percentage' => $activeWarrantyPercentage,
                    'expired_warranty_percentage' => $expiredWarrantyPercentage,
                    'available_troubleshooting_claims' => $availableTroubleshootingClaims,
                    'available_cable_management_claims' => $availableCableManagementClaims,
                    'available_dust_cleaning_claims' => $availableDustCleaningClaims,
                    'claim_rate' => $claimRate,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage(),
                'statistics' => []
            ], 500);
        }
    }

    /**
     * Resolve (or create) the ServeMps record for a given order — same
     * order-row quick-launch pattern as Craft Inspection, and ServeBek's
     * getByOrder(). Only valid for Prime Series orders (lkp_serve_id = 2);
     * the ServeData row itself is created by OrderController::updateserve()
     * on order approval, so this 404s if the order isn't approved yet.
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

            if ((int) $serveData->lkp_serve_id !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order is not on the Prime Series (MPS) tier.'
                ], 422);
            }

            $serveMps = ServeMps::withTrashed()->where('serve_data_id', $serveData->id)->first();

            if (!$serveMps) {
                // serve_mps_id is NOT NULL in the DB — must be set on create.
                $serveType = Serves::find($serveData->lkp_serve_id);
                $serveTypeCode = $serveType ? strtoupper($serveType->code) : 'MPS';
                $serveMpsNumber = str_pad(ServeMps::count() + 1, 4, '0', STR_PAD_LEFT);

                $serveMps = ServeMps::create([
                    'serve_mps_id' => "{$serveTypeCode}-{$serveMpsNumber}",
                    'serve_data_id' => $serveData->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => ['id' => $serveMps->id, 'serve_data_id' => $serveMps->serve_data_id],
                'message' => 'ServeMps record resolved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve ServeMps record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $serveData = ServeData::find($request->serve_data_id);
        $validated['qvse_cid'] = $serveData->qvse_cid;
        $lkp_serve_id = $serveData->serve_id;
        $serveType = Serves::find($lkp_serve_id);
        $serveTypeCode = $serveType ? strtoupper(substr($serveType->code, 0)) : 'MPS-0407-';
        $lastServeMps = ServeMps::orderBy('id', 'desc')->first();
        $sequence = $lastServeMps ?
            intval(substr($lastServeMps->id, -4)) + 1 : 1;
        $sequenceNumber = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        $serveId = "{$serveTypeCode}-{$sequenceNumber}";
        $request->merge(['serve_mps_id' => $serveId]);

        try {
            $validated = $request->validate([
                'serve_mps_id' => 'required',
                'serve_data_id' => 'required|exists:serve_data,id',
                'date_start' => 'required|date',
                'two_year_assembly_warranty' => 'boolean',
                'two_free_onsite_troubleshooting_first_6_months' => 'boolean',
                'two_free_onsite_troubleshooting_claim_1' => 'boolean',
                'two_free_onsite_troubleshooting_claim_1_date' => 'nullable|date',
                'two_free_onsite_troubleshooting_claim_2' => 'boolean',
                'two_free_onsite_troubleshooting_claim_2_date' => 'nullable|date',
                'two_advance_cable_management_first_year' => 'boolean',
                'two_advance_cable_management_claim_1' => 'boolean',
                'two_advance_cable_management_claim_1_date' => 'nullable|date',
                'two_advance_cable_management_claim_2' => 'boolean',
                'two_advance_cable_management_claim_2_date' => 'nullable|date',
                'one_free_dust_cleaning_first_year' => 'boolean',
                'one_free_dust_cleaning_claim' => 'boolean',
                'one_free_dust_cleaning_claim_date' => 'nullable|date',
                'fifty_percent_off_dust_cleaning_second_year' => 'boolean',
                'fifty_percent_off_dust_cleaning_claim_date' => 'nullable|date',
                'thirty_percent_off_labour_fees_upgrade_first_year' => 'boolean',
                'thirty_percent_off_labour_fees_claim_date' => 'nullable|date',
                'thirty_percent_off_dust_cleaning' => 'boolean',
                'thirty_percent_off_dust_cleaning_claim_date' => 'nullable|date',
                'rm100_promo_code_next_build' => 'nullable|string|max:100',
                'generate_code' => 'boolean',
                'rm100_promo_code_claim' => 'boolean',
                'notes' => 'nullable|string',
            ]);

            // Auto-generate promo code if requested
            if ($validated['generate_code'] && empty($validated['rm100_promo_code_next_build'])) {
                $validated['rm100_promo_code_next_build'] = $this->generatePromoCode();
            }

            $serveMps = ServeMps::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Serve MPS created successfully',
                'data' => $serveMps
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating Serve MPS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            // Use withTrashed() to include soft deleted records
            $serveMps = ServeMps::withTrashed()->find($id);

            if (!$serveMps) {
                return response()->json([
                    'success' => false,
                    'message' => 'Serve MPS not found show'
                ], 404);
            }

            // Get customer info if serve_data_id exists
            $customerInfo = null;
            if ($serveMps->serve_data_id) {
                $serveData = ServeData::with('customer')->find($serveMps->serve_data_id);
                if ($serveData && $serveData->customer) {
                    $customerInfo = [
                        'full_name' => $serveData->customer->full_name ?? null,
                        'email' => $serveData->customer->email ?? null,
                        'phone' => $serveData->customer->phone ?? null,
                    ];
                }
            }

            $responseData = [
                'id' => $serveMps->id,
                'serve_data_id' => $serveMps->serve_data_id,
                'qvse_cid' => $serveMps->qvse_cid,
                'date_start' => $serveMps->date_start ? $serveMps->date_start->format('Y-m-d') : null,
                'two_year_assembly_warranty' => (bool)$serveMps->two_year_assembly_warranty,
                'two_free_onsite_troubleshooting_first_6_months' => (bool)$serveMps->two_free_onsite_troubleshooting_first_6_months,
                'two_free_onsite_troubleshooting_claim_1' => (bool)$serveMps->two_free_onsite_troubleshooting_claim_1,
                'two_free_onsite_troubleshooting_claim_1_date' => $serveMps->two_free_onsite_troubleshooting_claim_1_date ? $serveMps->two_free_onsite_troubleshooting_claim_1_date->format('Y-m-d') : null,
                'two_free_onsite_troubleshooting_claim_2' => (bool)$serveMps->two_free_onsite_troubleshooting_claim_2,
                'two_free_onsite_troubleshooting_claim_2_date' => $serveMps->two_free_onsite_troubleshooting_claim_2_date ? $serveMps->two_free_onsite_troubleshooting_claim_2_date->format('Y-m-d') : null,
                'two_advance_cable_management_first_year' => (bool)$serveMps->two_advance_cable_management_first_year,
                'two_advance_cable_management_claim_1' => (bool)$serveMps->two_advance_cable_management_claim_1,
                'two_advance_cable_management_claim_1_date' => $serveMps->two_advance_cable_management_claim_1_date ? $serveMps->two_advance_cable_management_claim_1_date->format('Y-m-d') : null,
                'two_advance_cable_management_claim_2' => (bool)$serveMps->two_advance_cable_management_claim_2,
                'two_advance_cable_management_claim_2_date' => $serveMps->two_advance_cable_management_claim_2_date ? $serveMps->two_advance_cable_management_claim_2_date->format('Y-m-d') : null,
                'one_free_dust_cleaning_first_year' => (bool)$serveMps->one_free_dust_cleaning_first_year,
                'one_free_dust_cleaning_claim' => (bool)$serveMps->one_free_dust_cleaning_claim,
                'one_free_dust_cleaning_claim_date' => $serveMps->one_free_dust_cleaning_claim_date ? $serveMps->one_free_dust_cleaning_claim_date->format('Y-m-d') : null,
                'fifty_percent_off_dust_cleaning_second_year' => (bool)$serveMps->fifty_percent_off_dust_cleaning_second_year,
                'fifty_percent_off_dust_cleaning_claim_date' => $serveMps->fifty_percent_off_dust_cleaning_claim_date ? $serveMps->fifty_percent_off_dust_cleaning_claim_date->format('Y-m-d') : null,
                'thirty_percent_off_labour_fees_upgrade_first_year' => (bool)$serveMps->thirty_percent_off_labour_fees_upgrade_first_year,
                'thirty_percent_off_labour_fees_claim_date' => $serveMps->thirty_percent_off_labour_fees_claim_date ? $serveMps->thirty_percent_off_labour_fees_claim_date->format('Y-m-d') : null,
                'thirty_percent_off_dust_cleaning' => (bool)$serveMps->thirty_percent_off_dust_cleaning,
                'thirty_percent_off_dust_cleaning_claim_date' => $serveMps->thirty_percent_off_dust_cleaning_claim_date ? $serveMps->thirty_percent_off_dust_cleaning_claim_date->format('Y-m-d') : null,
                'rm100_promo_code_next_build' => $serveMps->rm100_promo_code_next_build,
                'generate_code' => (bool)$serveMps->generate_code,
                'rm100_promo_code_claim' => (bool)$serveMps->rm100_promo_code_claim,
                'notes' => $serveMps->notes,
                'created_at' => $serveMps->created_at,
                'updated_at' => $serveMps->updated_at,
                'deleted_at' => $serveMps->deleted_at,
                'customer' => $customerInfo,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Serve MPS retrieved successfully',
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving Serve MPS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $serveMps = ServeMps::withTrashed()->find($id);

            if (!$serveMps) {
                return response()->json([
                    'success' => false,
                    'message' => 'Serve MPS not found'
                ], 404);
            }

            $validated = $request->validate([
                'serve_data_id' => 'required|exists:serve_data,id',
                'date_start' => 'required|date',
                'two_year_assembly_warranty' => 'boolean',
                'two_free_onsite_troubleshooting_first_6_months' => 'boolean',
                'two_free_onsite_troubleshooting_claim_1' => 'boolean',
                'two_free_onsite_troubleshooting_claim_1_date' => 'nullable|date',
                'two_free_onsite_troubleshooting_claim_2' => 'boolean',
                'two_free_onsite_troubleshooting_claim_2_date' => 'nullable|date',
                'two_advance_cable_management_first_year' => 'boolean',
                'two_advance_cable_management_claim_1' => 'boolean',
                'two_advance_cable_management_claim_1_date' => 'nullable|date',
                'two_advance_cable_management_claim_2' => 'boolean',
                'two_advance_cable_management_claim_2_date' => 'nullable|date',
                'one_free_dust_cleaning_first_year' => 'boolean',
                'one_free_dust_cleaning_claim' => 'boolean',
                'one_free_dust_cleaning_claim_date' => 'nullable|date',
                'fifty_percent_off_dust_cleaning_second_year' => 'boolean',
                'fifty_percent_off_dust_cleaning_claim_date' => 'nullable|date',
                'thirty_percent_off_labour_fees_upgrade_first_year' => 'boolean',
                'thirty_percent_off_labour_fees_claim_date' => 'nullable|date',
                'thirty_percent_off_dust_cleaning' => 'boolean',
                'thirty_percent_off_dust_cleaning_claim_date' => 'nullable|date',
                'rm100_promo_code_next_build' => 'nullable|string|max:100', // UPDATED FIELD
                'generate_code' => 'boolean',
                'rm100_promo_code_claim' => 'boolean',
                'notes' => 'nullable|string',
            ]);

            // Update QVSE CID if serve_data_id changed
            if ($validated['serve_data_id'] != $serveMps->serve_data_id) {
                $serveData = ServeData::find($validated['serve_data_id']);
                $validated['qvse_cid'] = $serveData->qvse_cid;
            }

            // Auto-generate promo code if requested and not already set
            if ($validated['generate_code'] && empty($validated['rm100_promo_code_next_build'])) {
                $validated['rm100_promo_code_next_build'] = $this->generatePromoCode();
                $validated['rm100_promo_code_claim'] = false; // Reset claim status if new code generated
            }

            $serveMps->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Serve MPS updated successfully',
                'data' => $serveMps
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating Serve MPS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $serveMps = ServeMps::find($id);

            if (!$serveMps) {
                return response()->json([
                    'success' => false,
                    'message' => 'Serve MPS not found'
                ], 404);
            }

            $serveMps->delete();

            return response()->json([
                'success' => true,
                'message' => 'Serve MPS deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting Serve MPS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft deleted resource.
     */
    public function restore($id)
    {
        try {
            $serveMps = ServeMps::withTrashed()->find($id);

            if (!$serveMps) {
                return response()->json([
                    'success' => false,
                    'message' => 'Serve MPS not found'
                ], 404);
            }

            $serveMps->restore();

            return response()->json([
                'success' => true,
                'message' => 'Serve MPS restored successfully',
                'data' => $serveMps
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring Serve MPS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark promo code as claimed
     */
    public function markPromoClaimed($id)
    {
        try {
            $serveMps = ServeMps::find($id);

            if (!$serveMps) {
                return response()->json([
                    'success' => false,
                    'message' => 'Serve MPS not found'
                ], 404);
            }

            if (empty($serveMps->rm100_promo_code_next_build)) { // UPDATED FIELD
                return response()->json([
                    'success' => false,
                    'message' => 'No promo code to mark as claimed'
                ], 400);
            }

            $serveMps->update([
                'rm100_promo_code_claim' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promo code marked as claimed successfully',
                'data' => $serveMps
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking promo code as claimed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a random promo code
     */
    private function generatePromoCode()
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';

        for ($i = 0; $i < 8; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }

        return 'MPS-' . $code;
    }
}
