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
use App\Models\Customers;
use App\Models\Products;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
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

                    $order->care_price = $order->care_data->first()->price;
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

        if ($order->total <= 7000.00) {
            $lkp_serve_id = 1; // Inessential kit
        } elseif ($order->total > 10000.00) {
            $lkp_serve_id = 3; // Premium
        } else {
            $lkp_serve_id = 2; // Silver
        }

        $serve = Serves::findOrFail($lkp_serve_id);

        $calculateCareServiceCharge = function($totalAmount) use ($order) {
            $totalAmount = $order->total;
            if ($totalAmount >= 0 && $totalAmount <= 5999) {
                return ['lkp_care_id' => 1, 'care_charge' => 379, 'range' => '0 - 5,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 6000 && $totalAmount <= 6999) {
                return ['lkp_care_id' => 1, 'care_charge' => 479, 'range' => '6,000 - 6,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 7000 && $totalAmount <= 7999) {
                return ['lkp_care_id' => 2, 'care_charge' => 689, 'range' => '7,000 - 7,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 8000 && $totalAmount <= 8999) {
                return ['lkp_care_id' => 2, 'care_charge' => 789, 'range' => '8,000 - 8,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 9000 && $totalAmount <= 9999) {
                return ['lkp_care_id' => 2, 'care_charge' => 889, 'range' => '9,000 - 9,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 10000 && $totalAmount <= 10999) {
                return ['lkp_care_id' => 3, 'care_charge' => 1159, 'range' => '10,000 - 10,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 11000 && $totalAmount <= 11999) {
                return ['lkp_care_id' => 3, 'care_charge' => 1269, 'range' => '11,000 - 11,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 12000 && $totalAmount <= 12999) {
                return ['lkp_care_id' => 3, 'care_charge' => 1379, 'range' => '12,000 - 12,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 13000 && $totalAmount <= 13999) {
                return ['lkp_care_id' => 3, 'care_charge' => 1489, 'range' => '13,000 - 13,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 14000 && $totalAmount <= 14999) {
                return ['lkp_care_id' => 3, 'care_charge' => 1599, 'range' => '14,000 - 14,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 15000 && $totalAmount <= 15999) {
                return ['lkp_care_id' => 3, 'care_charge' => 1709, 'range' => '15,000 - 15,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 16000 && $totalAmount <= 16999) {
                return ['lkp_care_id' => 3, 'care_charge' => 1819, 'range' => '16,000 - 16,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 17000 && $totalAmount <= 17999) {
                return ['lkp_care_id' => 3, 'care_charge' => 1929, 'range' => '17,000 - 17,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 18000 && $totalAmount <= 18999) {
                return ['lkp_care_id' => 3, 'care_charge' => 2039, 'range' => '18,000 - 18,999', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 19000 && $totalAmount <= 20000) {
                return ['lkp_care_id' => 3, 'care_charge' => 2149, 'range' => '19,000 - 20,000', 'total_amount' => $totalAmount];
            } elseif ($totalAmount >= 20000 && $totalAmount <= 20999) {
                return ['lkp_care_id' => 3, 'care_charge' => 2239, 'range' => '20,000 - 20,999', 'total_amount' => $totalAmount];
            } else {
                return ['lkp_care_id' => 3, 'care_charge' => 2479, 'range' => 'Above 20,999', 'total_amount' => $totalAmount];;
            }
        };

        $care = Care::findOrFail($calculateCareServiceCharge('lkp_care_id')['lkp_care_id']);

        $care = collect($care->toArray())->merge([
            'care_charge' => $calculateCareServiceCharge('lkp_care_id')['care_charge']
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
                    'product_code' => $detail->product->product_code ?? null,
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
                    'product_code' => $product->product_code ?? 'N/A',
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
                $orderdetails = OrderDetails::where('order_id', $order->id)
                    ->with([
                        'product' => function($query) {
                            $query->select('id', 'product_name', 'product_code', 'image', 'cat_id', 'product_qty', 'price', 'product_code')
                                ->with('category:id,name');
                        }
                    ])->get();

                $careCategories = [2, 4, 5, 7, 8, 9, 10, 11];

                $calculateCareServiceCharge = function($totalAmount) use ($order) {
                    $totalAmount = $order->total;

                    if ($totalAmount >= 0 && $totalAmount <= 5999) {
                        return ['lkp_care_id' => 1, 'care_charge' => 379, 'range' => '0 - 5,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 6000 && $totalAmount <= 6999) {
                        return ['lkp_care_id' => 1, 'care_charge' => 479, 'range' => '6,000 - 6,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 7000 && $totalAmount <= 7999) {
                        return ['lkp_care_id' => 2, 'care_charge' => 689, 'range' => '7,000 - 7,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 8000 && $totalAmount <= 8999) {
                        return ['lkp_care_id' => 2, 'care_charge' => 789, 'range' => '8,000 - 8,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 9000 && $totalAmount <= 9999) {
                        return ['lkp_care_id' => 2, 'care_charge' => 889, 'range' => '9,000 - 9,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 10000 && $totalAmount <= 10999) {
                        return ['lkp_care_id' => 3, 'care_charge' => 1159, 'range' => '10,000 - 10,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 11000 && $totalAmount <= 11999) {
                        return ['lkp_care_id' => 3, 'care_charge' => 1269, 'range' => '11,000 - 11,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 12000 && $totalAmount <= 12999) {
                        return ['lkp_care_id' => 3, 'care_charge' => 1379, 'range' => '12,000 - 12,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 13000 && $totalAmount <= 13999) {
                        return ['lkp_care_id' => 3, 'care_charge' => 1489, 'range' => '13,000 - 13,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 14000 && $totalAmount <= 14999) {
                        return ['lkp_care_id' => 3, 'care_charge' => 1599, 'range' => '14,000 - 14,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 15000 && $totalAmount <= 15999) {
                        return ['lkp_care_id' => 3, 'care_charge' => 1709, 'range' => '15,000 - 15,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 16000 && $totalAmount <= 16999) {
                        return ['lkp_care_id' => 3, 'care_charge' => 1819, 'range' => '16,000 - 16,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 17000 && $totalAmount <= 17999) {
                        return ['lkp_care_id' => 3, 'care_charge' => 1929, 'range' => '17,000 - 17,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 18000 && $totalAmount <= 18999) {
                        return ['lkp_care_id' => 3, 'care_charge' => 2039, 'range' => '18,000 - 18,999', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 19000 && $totalAmount <= 20000) {
                        return ['lkp_care_id' => 3, 'care_charge' => 2149, 'range' => '19,000 - 20,000', 'total_amount' => $totalAmount];
                    } elseif ($totalAmount >= 20000 && $totalAmount <= 20999) {
                        return ['lkp_care_id' => 3, 'care_charge' => 2239, 'range' => '20,000 - 20,999', 'total_amount' => $totalAmount];
                    } else {
                        return ['lkp_care_id' => 3, 'care_charge' => 2479, 'range' => 'Above 20,999', 'total_amount' => $totalAmount];;
                    }
                };

                $careProductTotal = $orderdetails
                    ->filter(fn($item) => in_array($item->product->cat_id, $careCategories))
                    ->reduce(function ($carry, $item) use ($calculateCareServiceCharge,$order) {
                        $qty = $item->product->product_qty ?? 0;
                        $price = $item->product->price ?? 0;

                        // Calculate total amount properly (price * quantity)
                        $itemTotal = $price * $qty;

                        return [
                            'total_products' => $carry['total_products'] + 1,
                            'total_quantity' => (int)$order->qty,
                            'total_amount' => $calculateCareServiceCharge('care_charge'),
                        ];
                    }, ['total_products' => 0, 'total_quantity' => 0, 'total_amount' => 0]);

                $careServiceCharge = $calculateCareServiceCharge($careProductTotal['total_amount'] ?? 0);

                $lkp_care_id = $careServiceCharge['lkp_care_id'];
                $care_part_price = $careProductTotal['total_amount'] ?? 0;
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
                    $careTypeCode = $careType ? strtoupper(substr($careType->code, 0)) : 'QV-CARE';
                    $lastCare = CareData::where('lkp_care_id', $lkp_care_id)
                        ->orderBy('id', 'desc')
                        ->first();

                    $sequence = $lastCare ?
                        intval(substr($lastCare->care_id, -4)) + 1 : 1;
                    $sequenceNumber = str_pad($sequence, 4, '0', STR_PAD_LEFT);
                    $careId = "{$careTypeCode}-{$sequenceNumber}";

                    // Ensure uniqueness
                    while (CareData::where('care_id', $careId)->exists()) {
                        $sequence++;
                        $sequenceNumber = str_pad($sequence, 4, '0', STR_PAD_LEFT);
                        $careId = "{$careTypeCode}-{$sequenceNumber}";
                    }

                    $careData = CareData::create([
                        'care_id' => $careId,
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
                $totalServes = ServeData::count();
                $nextId = $totalServes + 1;
                $serveNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
                $serveId = "QV-SERV-{$serveNumber}";

                // Get serve type for QVSE CID generation based on order total
                if ($order->total <= 7000.00) {
                    $lkp_serve_id = 1; // Inessential kit
                } elseif ($order->total > 10000.00) {
                    $lkp_serve_id = 3; // Premium
                } else {
                    $lkp_serve_id = 2; // Silver
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
                    $serveBek = ServeBek::create([
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
                    $serveBek = ServeMps::create([
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
            } elseif ($approveValue === '1' || $approveValue === 1) {
                $approveValue = 1;
            }

            // Update the order
            $order->approve = $approveValue;
            $order->invoice_id = $invoiceId;

            // Set approved_at only when approving
            if ($approveValue == 1) {
                $order->approved_at = now();

                // Only create/update serve when approving
                $serveResponse = $this->updateserve($request, $order, $id);
                $careResponse = $this->updatecare($request, $order, $id);
                // dd($request->all());

                // Check if responses are valid
                if (!$serveResponse || !$careResponse) {
                    throw new \Exception('Failed to get response from serve or care functions');
                }

                // Decode responses
                $serveData = json_decode($serveResponse->getContent(), true);
                $careData = json_decode($careResponse->getContent(), true);

                if (!$serveData['success']) {
                    throw new \Exception('Failed to update serve data: ' . ($serveData['error'] ?? $serveData['message'] ?? 'Unknown error'));
                }
                if (!$careData['success']) {
                    throw new \Exception('Failed to update care data: ' . ($careData['error'] ?? $careData['message'] ?? 'Unknown error'));
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

                // Create order detail
                $orderDetail = OrderDetails::create([
                    'order_id' => $id,
                    'pro_id' => $productData['id'],
                    'pro_qty' => $productData['qty'],
                    'pro_price' => $productData['price'],
                    'sub_total' => $productData['qty'] * $productData['price']
                ]);

                // Decrement product stock
                $product->decrement('product_qty', $productData['qty']);

                $totalQty += $productData['qty'];
                $subTotal += $productData['qty'] * $productData['price'];
            }

            // Determine categories based on total
            if ($subTotal <= 7000.00) {
                $categories_id = 1; // Inessential kit
            } elseif ($subTotal > 10000.00) {
                $categories_id = 3; // Premium
            } else {
                $categories_id = 2; // Silver
            }

            // Update order
            $order->update([
                'customer_id' => $request->customer_id,
                'qty' => $totalQty,
                'sub_total' => $subTotal,
                'total' => $subTotal,
                'craft_id' => $categories_id,
                'serve_id' => $categories_id,
                'care_id' => $categories_id
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

        $careDistribution = Order::with('care')
            ->select('care_id', DB::raw('COUNT(*) as count'))
            ->groupBy('care_id')
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
