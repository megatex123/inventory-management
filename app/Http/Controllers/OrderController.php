<?php

namespace App\Http\Controllers;

use App\Models\ServeBek;
use App\Models\ServeMps;
use App\Models\ServePce;
use App\Models\CareData;
use App\Models\ServeData;
use App\Models\Care;
use App\Models\Serves;
use App\Models\Categories;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderDraft;
use App\Models\Customers;
use App\Support\BusinessId;
use App\Models\Products;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // QuiviCare RMA-eligible part categories: CPU, SSD, GPU, HDD, RAM, MBD,
    // PSU, HSF, AIO. Excludes CSE (Case — spec lists it as "optional", not
    // included by default), FAN, and all ACC-*/PER-* accessory/peripheral
    // categories, which the spec explicitly excludes.
    const CARE_ELIGIBLE_CATEGORIES = [1, 2, 3, 4, 5, 6, 7, 8, 11];

    /**
     * Sum of order_details.sub_total for products in a CARE-eligible category.
     * This — not the whole order total — is what should decide the QuiviCare tier.
     */
    private function eligibleCarePartsTotal($orderId)
    {
        return (float) OrderDetails::where('order_id', $orderId)
            ->whereHas('product', function ($q) {
                $q->whereIn('cat_id', self::CARE_ELIGIBLE_CATEGORIES);
            })
            ->sum('sub_total');
    }

    /**
     * QuiviCare tier + fee for a given eligible-parts amount. care table IDs
     * are 1=COR3, 2=RI5E, 3=VIS10N (already in ascending order). Fee brackets
     * are business-defined RM amounts — kept as-is, only the input changed
     * from the whole order total to the eligible-parts-only total.
     */
    private function resolveCareTier($amount)
    {
        if ($amount <= 5999) {
            return ['lkp_care_id' => 1, 'care_charge' => 379, 'range' => '0 - 5,999'];
        } elseif ($amount <= 6999) {
            return ['lkp_care_id' => 1, 'care_charge' => 479, 'range' => '6,000 - 6,999'];
        } elseif ($amount <= 7999) {
            return ['lkp_care_id' => 2, 'care_charge' => 689, 'range' => '7,000 - 7,999'];
        } elseif ($amount <= 8999) {
            return ['lkp_care_id' => 2, 'care_charge' => 789, 'range' => '8,000 - 8,999'];
        } elseif ($amount <= 9999) {
            return ['lkp_care_id' => 2, 'care_charge' => 889, 'range' => '9,000 - 9,999'];
        } elseif ($amount <= 10999) {
            return ['lkp_care_id' => 3, 'care_charge' => 1159, 'range' => '10,000 - 10,999'];
        } elseif ($amount <= 11999) {
            return ['lkp_care_id' => 3, 'care_charge' => 1269, 'range' => '11,000 - 11,999'];
        } elseif ($amount <= 12999) {
            return ['lkp_care_id' => 3, 'care_charge' => 1379, 'range' => '12,000 - 12,999'];
        } elseif ($amount <= 13999) {
            return ['lkp_care_id' => 3, 'care_charge' => 1489, 'range' => '13,000 - 13,999'];
        } elseif ($amount <= 14999) {
            return ['lkp_care_id' => 3, 'care_charge' => 1599, 'range' => '14,000 - 14,999'];
        } elseif ($amount <= 15999) {
            return ['lkp_care_id' => 3, 'care_charge' => 1709, 'range' => '15,000 - 15,999'];
        } elseif ($amount <= 16999) {
            return ['lkp_care_id' => 3, 'care_charge' => 1819, 'range' => '16,000 - 16,999'];
        } elseif ($amount <= 17999) {
            return ['lkp_care_id' => 3, 'care_charge' => 1929, 'range' => '17,000 - 17,999'];
        } elseif ($amount <= 18999) {
            return ['lkp_care_id' => 3, 'care_charge' => 2039, 'range' => '18,000 - 18,999'];
        } elseif ($amount <= 19999) {
            return ['lkp_care_id' => 3, 'care_charge' => 2149, 'range' => '19,000 - 19,999'];
        } elseif ($amount <= 20999) {
            return ['lkp_care_id' => 3, 'care_charge' => 2239, 'range' => '20,000 - 20,999'];
        } else {
            return ['lkp_care_id' => 3, 'care_charge' => 2479, 'range' => 'Above 20,999'];
        }
    }

    public function getorders()
    {
        $orders = Order::with([
                'customer',
                'craft',
                'serve',
                'care',
                'care_data'
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function($order) {
                if ($order->approved_at) {
                    $today = Carbon::now();
                    $approvedAt = Carbon::parse($order->approved_at);
                    $expiryDate = $approvedAt->copy()->addMonths(6);

                    // No CareData exists when the customer opted out
                    // (skip_quivicare) at checkout -- guard against the null.
                    $order->care_price = optional($order->care_data->first())->price;
                    // dd($order->price);

                    if ($today->gt($expiryDate)) {
                        $order->time_remaining = "Expired";
                        $order->months_remaining = 0;
                        $order->days_remaining = 0;
                    } else {
                        $diff = $today->diff($expiryDate);

                        $order->months_remaining = $diff->m + ($diff->y * 12);
                        $order->days_remaining = $diff->d;

                        $order->time_remaining = $order->months_remaining . " Months " . $order->days_remaining . " Days";
                    }

                    $order->range_start = $today->toDateTimeString();
                    $order->range_end = $expiryDate->toDateTimeString();
                } else {
                    $order->time_remaining = "Not Approved";
                    $order->months_remaining = null;
                    $order->days_remaining = null;
                    $order->range_start = null;
                    $order->range_end = null;
                }
                return $order;
            });

        return response()->json($orders);
    }

    /**
     * Orders placed today — same "today" definition already used by getStatistics()
     * (Order::whereDate('order_date', Carbon::today())), so the Today's Orders page
     * and the All Orders page's "Today's Summary" card never disagree.
     */
    public function today()
    {
        $orders = Order::with([
                'customer',
                'craft',
                'serve',
                'care',
                'care_data'
            ])
            ->whereDate('order_date', Carbon::today())
            ->orderByDesc('id')
            ->get()
            ->map(function($order) {
                if ($order->approved_at) {
                    $today = Carbon::now();
                    $approvedAt = Carbon::parse($order->approved_at);
                    $expiryDate = $approvedAt->copy()->addMonths(6);

                    // No CareData exists when the customer opted out
                    // (skip_quivicare) at checkout -- guard against the null.
                    $order->care_price = optional($order->care_data->first())->price;

                    if ($today->gt($expiryDate)) {
                        $order->time_remaining = "Expired";
                        $order->months_remaining = 0;
                        $order->days_remaining = 0;
                    } else {
                        $diff = $today->diff($expiryDate);

                        $order->months_remaining = $diff->m + ($diff->y * 12);
                        $order->days_remaining = $diff->d;

                        $order->time_remaining = $order->months_remaining . " Months " . $order->days_remaining . " Days";
                    }
                } else {
                    $order->time_remaining = "Not Approved";
                    $order->months_remaining = null;
                    $order->days_remaining = null;
                }
                return $order;
            });

        return response()->json($orders);
    }

    public function details($id)
    {
        $order = Order::with(['customer', 'craft', 'serve', 'care', 'serve_data.serve', 'care_data.care'])->findOrFail($id);

        // $orderdetails = OrderDetails::where('order_id', $id)
        //             ->with([
        //                 'product' => function($query) {
        //                     $query->select('id', 'product_name', 'product_code', 'image', 'cat_id', 'product_qty', 'price', 'product_code')
        //                         ->with('category:id,name');
        //                 }
        //             ])->get();

        // QuiviServe tier — < RM7,000 Essential Kit, RM7,000-9,999 Prime Series,
        // >= RM10,000 Collector's Edition (serves table: 1/2/3 in that order already).
        if ($order->total < 7000.00) {
            $lkp_serve_id = 1; // Essential Kit
        } elseif ($order->total < 10000.00) {
            $lkp_serve_id = 2; // Prime Series
        } else {
            $lkp_serve_id = 3; // Collector's Edition
        }

        $serve = Serves::findOrFail($lkp_serve_id);

        // QuiviCare tier is based on the sum of RMA-eligible parts only, not
        // the whole order total (see self::CARE_ELIGIBLE_CATEGORIES).
        $careTier = $this->resolveCareTier($this->eligibleCarePartsTotal($order->id));

        $care = Care::findOrFail($careTier['lkp_care_id']);

        $care = collect($care->toArray())->merge([
            'care_charge' => $careTier['care_charge']
        ]);

        return response()->json([
            'order' => $order,
            'serve' => $serve,
            'care' => $care
        ]);
    }

    public function orderdetails($id)
    {
        // Using Eloquent with relationships
        $orderdetails = OrderDetails::where('order_id', $id)
            ->with([
                'product' => function($query) {
                    $query->select('id', 'product_name', 'product_code', 'image', 'cat_id', 'is_care','product_code');
                },
                'product.category' => function($query) {
                    $query->select('id', 'name');
                },
                'order.craft' => function($query) {
                    $query->select('id', 'name');
                },
                'order.serve' => function($query) {
                    $query->select('id', 'name');
                },
                'order.care' => function($query) {
                    $query->select('id', 'name');
                }
            ])
            ->get()
            ->map(function($detail) {
                return [
                    'id' => $detail->id,
                    'product_name' => $detail->product->product_name ?? null,
                    'product_code' => $detail->product->product_code ?? null,
                    'image' => $detail->product->image ?? null,
                    'pro_id' => $detail->pro_id,
                    'order_id' => $detail->order_id,
                    'pro_qty' => $detail->pro_qty,
                    'pro_price' => $detail->pro_price,
                    'sub_total' => $detail->sub_total,
                    $category_id = $detail->product->cat_id,
                    $category = Categories::where('id',$category_id),
                    'category_name' => $category->pluck('name')->first() ?? null,
                    'is_care' => $detail->product->is_care ?? null,
                    'craft_name' => $detail->order->craft->name ?? null,
                    'serve_name' => $detail->order->serve->name ?? null,
                    'care_name' => $detail->order->care->name ?? null,
                ];
            });

        return response()->json($orderdetails);
    }

    public function getOrderWithDetails($id)
    {
        try {
            $order = Order::with(['customer', 'craft', 'serve', 'care', 'serve_data', 'care_data'])->findOrFail($id);
            $details = OrderDetails::where('order_id', $id)->with('product.category')->get()
            ->map(function($detail) {
                $product = $detail->product;
                $categoryName = 'N/A';
                if ($product && $product->category) {
                    $categoryName = $product->category->name;
                }
                return [
                    'id' => $detail->id,
                    'pro_id' => $detail->pro_id,
                    'product_code' => $product->product_code ?? 'N/A',
                    'product_name' => $product->product_name ?? 'N/A',
                    'product_image' => $product->image ?? null,
                    'pro_price' => floatval($detail->pro_price),
                    'pro_qty' => intval($detail->pro_qty),
                    'sub_total' => floatval($detail->sub_total),
                    'product_qty' => $product ? ($product->product_qty + $detail->pro_qty) : 0,
                    'original_qty' => $detail->pro_qty,
                    $category_id = $product->cat_id,
                    $category = Categories::where('id',$category_id),
                    'cat_id' => $category->pluck('name')->first(),
                    'cat' => $category->pluck('id')->first(),
                    'is_care' => $product->is_care ?? 'N/A',
                ];
            });
            return response()->json([
                'success' => true,
                'order' => $order,
                'details' => $details
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function updatecare(Request $request, $order, $id)
    {
        try {
            DB::beginTransaction();

            $careData = CareData::withTrashed()->where('order_id', $order->id)->first();
            $message = '';
            $careProductTotal = ['total_products' => 0, 'total_quantity' => 0, 'total_amount' => 0];
            $careServiceCharge = ['lkp_care_id' => 1, 'charge' => 0, 'range' => ''];

            if (!$careData) {
                // QuiviCare tier is based on the sum of RMA-eligible parts only
                // (self::CARE_ELIGIBLE_CATEGORIES), not the whole order total.
                $eligibleOrderDetails = OrderDetails::where('order_id', $order->id)
                    ->whereHas('product', function ($q) {
                        $q->whereIn('cat_id', self::CARE_ELIGIBLE_CATEGORIES);
                    })
                    ->get();

                $eligibleTotal = (float) $eligibleOrderDetails->sum('sub_total');

                $careProductTotal = [
                    'total_products' => $eligibleOrderDetails->count(),
                    'total_quantity' => (int) $eligibleOrderDetails->sum('pro_qty'),
                    'total_amount' => $eligibleTotal,
                ];

                $careServiceCharge = $this->resolveCareTier($eligibleTotal);

                $lkp_care_id = $careServiceCharge['lkp_care_id'];
                $care_part_price = $eligibleTotal;
                $care_charge = $careServiceCharge['care_charge'];

                if ($careData) {
                    $careData->update([
                        'customer_id' => $order->customer_id,
                        'lkp_care_id' => $lkp_care_id,
                        'total_part' => (int)$order->total,
                        'price' => $care_charge,
                    ]);

                    $message = 'Care data updated successfully';
                } else {
                    $careType = Care::find($lkp_care_id);
                    // Fallback prefix intentionally matches care_data_id's own 'QV-CARE-'
                    // prefix below -- harmless, since BusinessId::next() scopes its
                    // lookup to this specific column, not across columns.
                    $careTypeCode = $careType ? strtoupper(substr($careType->code, 0)) : 'QV-CARE';
                    $careId = BusinessId::next('care_data', 'care_id', "{$careTypeCode}-", 4);
                    $careDataId = BusinessId::next('care_data', 'care_data_id', 'QV-CARE-', 6);

                    $careData = CareData::create([
                        'care_id' => $careId,
                        'care_data_id' => $careDataId,
                        'customer_id' => $order->customer_id,
                        'order_id' => $order->id,
                        'lkp_care_id' => $lkp_care_id,
                        'total_part' => (int)$order->total,
                        'price' => $care_charge,
                    ]);

                    $message = 'Care data created successfully';
                }

                DB::commit();
            } else {
                // Restore soft-deleted record
                CareData::withTrashed()->where('order_id', $order->id)->restore();
                $careData = CareData::where('order_id', $order->id)->first();
                $message = 'Care data restored successfully';
                DB::commit();
            }

            return response()->json([
                'success' => true,
                'data' => $careData ? $careData->load(['customer', 'order', 'care']) : null,
                'message' => $message,
                'calculation_details' => [
                    'care_product_total' => $careProductTotal,
                    'service_charge_details' => $careServiceCharge
                ]
            ], $careData && $careData->wasRecentlyCreated ? 201 : 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process care data',
                'error' => $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function updateserve(Request $request, $order, $id)
    {
        try {
            DB::beginTransaction();

            // Find existing serve data for this order (assuming one serve per order)
            $serveData = ServeData::withTrashed()->where('order_id', $order->id)->first();

            if (!$serveData) {

                $totalServesPce = ServePce::count();
                $nextId = $totalServesPce + 1;
                $serveNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
                $serve_pce_id = "PCE-2610-{$serveNumber}";

                // Create new serve data if it doesn't exist
                $serveId = BusinessId::next('serve_data', 'serve_id', 'QV-SRV-', 6);

                // Get serve type for QVSE CID generation based on order total
                // QuiviServe tier — < RM7,000 Essential Kit, RM7,000-9,999 Prime
                // Series, >= RM10,000 Collector's Edition.
                if ($order->total < 7000.00) {
                    $lkp_serve_id = 1; // Essential Kit
                } elseif ($order->total < 10000.00) {
                    $lkp_serve_id = 2; // Prime Series
                } else {
                    $lkp_serve_id = 3; // Collector's Edition
                }

                $serveType = Serves::find($lkp_serve_id);
                $serveTypeCode = $serveType ? strtoupper(substr($serveType->code, 0)) : 'GEN';

                // Find next sequence for this serve type
                $lastServe = ServeData::where('lkp_serve_id', $lkp_serve_id)
                    ->orderBy('id', 'desc')
                    ->first();

                $sequence = $lastServe ?
                    intval(substr($lastServe->qvse_cid, -4)) + 1 : 1;
                $sequenceNumber = str_pad($sequence, 4, '0', STR_PAD_LEFT);

                $qvseCid = "{$serveTypeCode}-{$sequenceNumber}";

                // Create new serve data using order information
                $serveData = ServeData::create([
                    'serve_id' => $serveId,
                    'qvse_cid' => $qvseCid,
                    'customer_id' => $order->customer_id,
                    'order_id' => $order->id,
                    'lkp_serve_id' => $lkp_serve_id,
                ]);
                DB::commit();
                $serveData->load(['customer', 'order', 'serve']);

                $serveData->pluck('id')->last();


                if($lkp_serve_id == 1){
                    // serve_bek_id is NOT NULL in the DB — must be set on create,
                    // same as serve_pce_id is for the PCE branch below.
                    $totalServeBeks = ServeBek::count();
                    $serveBekNumber = str_pad($totalServeBeks + 1, 4, '0', STR_PAD_LEFT);
                    $serveBek = ServeBek::create([
                        'serve_bek_id' => "{$serveTypeCode}-{$serveBekNumber}",
                        'serve_data_id' => $serveData->id,
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'data' => $this->formatServeBekItem($serveBek->load('serveData')),
                        'message' => 'ServeBek record created successfully.'
                    ], 201);
                }

                elseif($lkp_serve_id == 2){
                    // serve_mps_id is NOT NULL in the DB — must be set on create.
                    $totalServeMps = ServeMps::count();
                    $serveMpsNumber = str_pad($totalServeMps + 1, 4, '0', STR_PAD_LEFT);
                    $serveBek = ServeMps::create([
                        'serve_mps_id' => "{$serveTypeCode}-{$serveMpsNumber}",
                        'serve_data_id' => $serveData->id,
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'data' => $this->formatServeBekItem($serveBek->load('serveData')),
                        'message' => 'ServeBek record created successfully.'
                    ], 201);
                }

                else{
                    $serveBek = ServePce::create([
                        'serve_pce_id' => $serve_pce_id,
                        'serve_data_id' => $serveData->id,
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'data' => $this->formatServeBekItem($serveBek->load('serveData')),
                        'message' => 'ServeBek record created successfully.'
                    ], 201);
                }
            }
            else{
                ServeData::withTrashed()->where('order_id', $order->id)->restore();
                $serveData = ServeData::where('order_id', $order->id)->first();
                DB::commit();
            }

            // Return the response
            return response()->json([
                'success' => true,
                'message' => 'Serve data updated successfully',
                'data' => $serveData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update serve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function formatServeBekItem($item)
    {
        return [
            'id' => $item->id,
            'serve_data_id' => $item->serve_data_id,
            'created_at' => $item->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $item->updated_at->format('Y-m-d H:i:s'),
            'serve_data' => $item->serveData ? [
                'id' => $item->serveData->id,
                'qvse_cid' => $item->serveData->qvse_cid,
                // Add other serve_data fields as needed
            ] : null,
        ];
    }

    public function updateApprove(Request $request, $id)
    {
        $request->validate([
            'approve' => 'nullable',
            'invoice_id' => 'nullable',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::findOrFail($id);

            // Get the approve value
            $approveValue = $request->approve;

            // $invoiceTypeCode = "QVT-INV";
            // $lastInvoice = Order::orderBy('id', 'desc')->first();
            // $sequence = $lastInvoice ? intval(substr($lastInvoice->invoice_id, -4)) + 1 : 1;
            // $sequenceNumber = str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // $invoiceId = "{$invoiceTypeCode}-{$sequenceNumber}";

            $datePart = Carbon::now()->format('ym');
            $paddedId = $id;
            $invoiceId = "QVT-INV-{$datePart}-{$paddedId}";

            // Handle different input types
            if ($approveValue === 'null' || $approveValue === null) {
                $approveValue = null;
            } elseif ($approveValue === '0' || $approveValue === 0) {
                $approveValue = 0;
                $reject_id = BusinessId::next('order', 'reject_id', 'BLDP-REJ-', 6);
            } elseif ($approveValue === '1' || $approveValue === 1) {
                $approveValue = 1;
                $reject_id = null; // Clear reject_id when approving
            }

            // Update the order
            $order->approve = $approveValue;
            $order->invoice_id = $invoiceId;
            $order->reject_id = $reject_id ?? null;
            // Set approved_at only when approving
            if ($approveValue == 1) {
                $order->approved_at = now();

                // Only create/update serve when approving
                $serveResponse = $this->updateserve($request, $order, $id);
                // dd($request->all());

                // Check if responses are valid
                if (!$serveResponse) {
                    throw new \Exception('Failed to get response from serve function');
                }

                // Decode responses
                $serveData = json_decode($serveResponse->getContent(), true);

                if (!$serveData['success']) {
                    throw new \Exception('Failed to update serve data: ' . ($serveData['error'] ?? $serveData['message'] ?? 'Unknown error'));
                }

                // Customer opted out of QuiviCare at checkout — skip creating/updating it.
                if (!$order->skip_quivicare) {
                    $careResponse = $this->updatecare($request, $order, $id);

                    if (!$careResponse) {
                        throw new \Exception('Failed to get response from care function');
                    }

                    $careData = json_decode($careResponse->getContent(), true);

                    if (!$careData['success']) {
                        throw new \Exception('Failed to update care data: ' . ($careData['error'] ?? $careData['message'] ?? 'Unknown error'));
                    }
                }
            } else {
                $order->approved_at = null;

                DB::beginTransaction();

                try {
                    $serveData = ServeData::where('order_id', $order->id)->firstOrFail();
                    $serveData->delete();
                    $careData = CareData::where('order_id', $order->id)->firstOrFail();
                    $careData->delete();

                    DB::commit();

                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    DB::rollBack();

                } catch (\Exception $e) {
                    DB::rollBack();

                }

            }

            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Approval updated successfully',
                'data' => [
                    'approve' => $order->approve,
                    'approved_at' => $order->approved_at
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update approval',
                'error' => $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function updateOrderDetails(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $order = Order::findOrFail($id);

            if ($order->approve !== null) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Cannot edit an order that has already been approved or rejected.',
                ], 422);
            }

            // Validate request
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'products' => 'required|array',
                'products.*.id' => 'required|exists:products,id',
                'products.*.qty' => 'required|integer|min:1',
                'products.*.price' => 'required|numeric|min:0',
            ]);

            // Get current order details to restore stock
            $currentDetails = OrderDetails::where('order_id', $id)->get();

            // Restore stock for current items before deleting
            foreach ($currentDetails as $detail) {
                if ($product = Products::find($detail->pro_id)) {
                    $product->increment('product_qty', $detail->pro_qty);
                }
            }

            // Delete existing order details
            OrderDetails::where('order_id', $id)->delete();

            $totalQty = 0;
            $subTotal = 0;
            $eligibleCareTotal = 0;
            $detailsSnapshot = [];

            // Insert new order details
            foreach ($request->products as $productData) {
                // Check if product exists and has sufficient stock
                $product = Products::find($productData['id']);
                if (!$product) {
                    throw new \Exception("Product not found: " . $productData['id']);
                }

                // if ($product->product_qty < $productData['qty']) {
                //     throw new \Exception("Insufficient stock for product: " . $product->product_name);
                // }

                $lineSubTotal = $productData['qty'] * $productData['price'];

                // Create order detail
                $orderDetail = OrderDetails::create([
                    'order_id' => $id,
                    'pro_id' => $productData['id'],
                    'pro_qty' => $productData['qty'],
                    'pro_price' => $productData['price'],
                    'sub_total' => $lineSubTotal
                ]);

                // Decrement product stock
                $product->decrement('product_qty', $productData['qty']);

                $lineTotal = $productData['qty'] * $productData['price'];
                $totalQty += $productData['qty'];
                $subTotal += $lineTotal;

                if (in_array($product->cat_id, self::CARE_ELIGIBLE_CATEGORIES)) {
                    $eligibleCareTotal += $lineTotal;
                }

                $detailsSnapshot[] = [
                    'pro_id' => $productData['id'],
                    'product_name' => $product->product_name,
                    'pro_qty' => $productData['qty'],
                    'pro_price' => $productData['price'],
                    'sub_total' => $lineSubTotal,
                ];
            }

            // QuiviCraft build-class tier — see PosController::orderdone() for the
            // same bands. craft table IDs are 1=BASIC, 2=PREMIUM, 3=MEDIUM, 4=ULTRA.
            if ($subTotal <= 6999.00) {
                $craftId = 1; // BASIC
            } elseif ($subTotal <= 9999.00) {
                $craftId = 3; // MEDIUM
            } elseif ($subTotal <= 19999.00) {
                $craftId = 2; // PREMIUM
            } else {
                $craftId = 4; // ULTRA
            }

            // QuiviServe tier — < RM7,000 Essential Kit, RM7,000-9,999 Prime
            // Series, >= RM10,000 Collector's Edition (serves table IDs 1/2/3
            // already in that order).
            if ($subTotal < 7000.00) {
                $serveTierId = 1; // Essential Kit
            } elseif ($subTotal < 10000.00) {
                $serveTierId = 2; // Prime Series
            } else {
                $serveTierId = 3; // Collector's Edition
            }

            // QuiviCare tier — based on the sum of RMA-eligible parts only
            // ($eligibleCareTotal, accumulated above), not the whole order total.
            $careTierId = $this->resolveCareTier($eligibleCareTotal)['lkp_care_id'];

            $getCraftTagId = $order->craft_tag_id;
            $updateCraftTag = null;
            if ($getCraftTagId && preg_match('/^(.*?)(\d+)$/', $getCraftTagId, $matches)) {
                $updateCraftTag = $matches[1] . str_pad((int)$matches[2] + 1, strlen($matches[2]), '0', STR_PAD_LEFT);
            }

            // Update order
            $order->update([
                'customer_id' => $request->customer_id,
                'qty' => $totalQty,
                'sub_total' => $subTotal,
                'total' => $subTotal,
                'craft_id' => $craftId,
                'serve_id' => $serveTierId,
                'care_id' => $careTierId,
                'craft_tag_id' => $updateCraftTag
            ]);

            // Snapshot this edit as a new draft revision -- see
            // docs/superpowers/specs/2026-08-01-quivicraft-draft-history-design.md.
            // Every successful edit produces exactly one new revision
            // reflecting the RESULT of that edit (draft -01 is the state
            // after the first edit, not the original creation state).
            OrderDraft::create([
                'order_id' => $order->id,
                'draft_id' => OrderDraft::nextDraftId($order),
                'customer_id' => $order->customer_id,
                'qty' => $order->qty,
                'sub_total' => $order->sub_total,
                'total' => $order->total,
                'craft_id' => $order->craft_id,
                'serve_id' => $order->serve_id,
                'care_id' => $order->care_id,
                'order_details_snapshot' => $detailsSnapshot,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order updated successfully',
                'order' => $order,
                'total' => $subTotal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function getStatistics()
    {
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total');
        $totalApproved = Order::where('approve', 1)->count();
        // Get draft orders count (not approved)
        $totalDraft = Order::whereNull('approve')->count();

        // Get expired orders count (approved and older than 6 months)
        $expiredOrders = Order::where('approve', 1)
            ->where('approved_at', '<=', Carbon::now()->subMonths(6))
            ->count();

        // Get active orders count (approved and not expired - within 6 months or no date)
        $activeOrders = Order::where('approve', 1)
            ->where(function($query) {
                $query->where('approved_at', '>', Carbon::now()->subMonths(6))
                    ->orWhereNull('approved_at');
            })
            ->count();

        // Get monthly statistics for the current year
        $monthlyStats = Order::select(
                DB::raw('MONTH(order_date) as month'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total) as total_revenue')
            )
            ->whereYear('order_date', date('Y'))
            ->groupBy(DB::raw('MONTH(order_date)'))
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->month => [
                    'orders' => $item->total_orders,
                    'revenue' => $item->total_revenue
                ]];
            });

        // Get top customers
        $topCustomers = Order::with('customer')
            ->select('customer_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total) as total_spent'))
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'customer' => $item->customer->full_name ?? 'Unknown',
                    'order_count' => $item->order_count,
                    'total_spent' => $item->total_spent
                ];
            });

        // Get top products
        $topProducts = OrderDetails::with('product')
            ->select('pro_id', DB::raw('SUM(pro_qty) as total_qty'), DB::raw('SUM(sub_total) as total_revenue'))
            ->groupBy('pro_id')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'product' => $item->product->product_name ?? 'Unknown',
                    'total_qty' => $item->total_qty,
                    'total_revenue' => $item->total_revenue
                ];
            });

        // Get service/care/craft distribution
        $craftDistribution = Order::with('craft')
            ->select('craft_id', DB::raw('COUNT(*) as count'))
            ->groupBy('craft_id')
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->craft->name ?? 'Unknown',
                    'count' => $item->count
                ];
            });

        $serveDistribution = Order::with('serve')
            ->select('serve_id', DB::raw('COUNT(*) as count'))
            ->groupBy('serve_id')
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->serve->name ?? 'Unknown',
                    'count' => $item->count,
                    'color' => $item->serve->colour ?? '#cccccc'
                ];
            });

        // Grouped from CareData.lkp_care_id (the actual assigned tier) rather
        // than Order.care_id, which is only set provisionally at order-creation
        // time and can drift out of sync — CareData's tier is recalculated from
        // eligible-parts total at approval and is what the QuiviCare list shows.
        $careDistribution = CareData::with('care')
            ->select('lkp_care_id', DB::raw('COUNT(*) as count'))
            ->groupBy('lkp_care_id')
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->care->name ?? 'Unknown',
                    'count' => $item->count
                ];
            });

        return response()->json([
            'overview' => [
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'total_approved' => $totalApproved,
                'total_draft' => $totalDraft,
                'active_orders' => $activeOrders,
                'expired_orders' => $expiredOrders
            ],
            'monthly_stats' => $monthlyStats,
            'top_customers' => $topCustomers,
            'top_products' => $topProducts,
            'craft_distribution' => $craftDistribution,
            'serve_distribution' => $serveDistribution,
            'care_distribution' => $careDistribution,
            'today_orders' => Order::whereDate('order_date', Carbon::today())->count(),
            'today_revenue' => Order::whereDate('order_date', Carbon::today())->sum('total')
        ]);
    }

}
