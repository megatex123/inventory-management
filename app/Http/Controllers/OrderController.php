<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Customers;
use App\Models\Products;
use Illuminate\Support\Facades\DB;
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
                'care'
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function($order) {
                if ($order->approved_at) {
                    $today = Carbon::now();
                    $approvedAt = Carbon::parse($order->approved_at);
                    $expiryDate = $approvedAt->copy()->addMonths(6);

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
        // Using Eloquent instead of raw query
        $order = Order::with(['customer', 'craft', 'serve', 'care'])
            ->findOrFail($id);

        return response()->json($order);
    }

    public function orderdetails($id)
    {
        // Using Eloquent with relationships
        $orderdetails = OrderDetails::where('order_id', $id)
            ->with([
                'product' => function($query) {
                    $query->select('id', 'product_name', 'product_code', 'image', 'cat_id');
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
            $order = Order::with(['customer', 'craft', 'serve', 'care'])
                ->findOrFail($id);

            $details = OrderDetails::where('order_id', $id)
                ->with('product.category')
                ->get()
                ->map(function($detail) {
                    $product = $detail->product;
                    $categoryName = 'N/A';

                    if ($product && $product->category) {
                        $categoryName = $product->category->name;
                    }

                    return [
                        'id' => $detail->id,
                        'pro_id' => $detail->pro_id,
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

    public function updatecraft(Request $request, $id)
    {
        $request->validate([
            'categories_id' => 'required|exists:craft,id', // Changed from care to craft
        ]);

        $order = Order::findOrFail($id);

        $order->update([
            'craft_id' => $request->categories_id,
        ]);

        return response()->json([
            'message' => 'Craft updated successfully',
            'craft_id' => $request->categories_id,
        ]);
    }

    public function updateserve(Request $request, $id)
    {
        $request->validate([
            'serve_id' => 'required|exists:serves,id',
        ]);

        $order = Order::findOrFail($id);

        $order->update([
            'serve_id' => $request->serve_id,
        ]);

        return response()->json([
            'message' => 'Serve updated successfully',
            'serve_id' => $request->serve_id,
        ]);
    }

    public function updatecare(Request $request, $id)
    {
        $request->validate([
            'care_id' => 'required|exists:care,id',
        ]);

        $order = Order::findOrFail($id);

        $order->update([
            'care_id' => $request->care_id,
        ]);

        return response()->json([
            'message' => 'Care updated successfully',
            'care_id' => $request->care_id,
        ]);
    }

    public function updateApprove(Request $request, $id)
    {
        $request->validate([
            'approve' => 'nullable', // Allow null, 0, 1
        ]);

        $order = Order::findOrFail($id);

        // Get the approve value
        $approveValue = $request->approve;

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

        // Set approved_at only when approving
        if ($approveValue == 1) {
            $order->approved_at = now();
        } else {
            $order->approved_at = null;
        }

        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Approval updated successfully',
            'data' => [
                'approve' => $order->approve,
                'approved_at' => $order->approved_at
            ]
        ]);
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
        $totalDraft = Order::where('approve', '!=', 1)->count();

        // Get expired orders count
        $expiredOrders = Order::where('approve', 1)
            ->where('approved_at', '<=', Carbon::now()->subMonths(6))
            ->count();

        // Get active orders count (approved and not expired)
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
            'serve_distribution' => $serveDistribution,
            'care_distribution' => $careDistribution,
            'today_orders' => Order::whereDate('order_date', Carbon::today())->count(),
            'today_revenue' => Order::whereDate('order_date', Carbon::today())->sum('total')
        ]);
    }

}
