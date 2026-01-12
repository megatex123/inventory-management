<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    // public function getorders()
    // {
    //     $date = date('d/m/Y');

    //     $orders = DB::table('order as o')
    //         ->join('customers as c', 'o.customer_id', '=', 'c.id')
    //         ->leftJoin('craft as cra', 'o.craft_id', '=', 'cra.id')
    //         ->leftJoin('serves as s', 'o.serve_id', '=', 's.id')
    //         ->leftJoin('care as ca', 'o.care_id', '=', 'ca.id')
    //         // ->where('o.order_date', $date)
    //         ->select(
    //             'o.*',
    //             'c.full_name',
    //             's.name as serve_name',
    //             's.colour as serve_colour',
    //             'ca.name as care_name',
    //             'cra.name as craft_name',
    //             'cra.fee as craft_fee',
    //             's.fee as serve_fee',
    //             'ca.fee as care_fee',
    //         )
    //         ->orderBy('o.id', 'DESC')
    //         ->get();
    //     // dd($orders);

    //     return response()->json($orders);
    // }

    public function getorders()
    {
        $orders = Order::with([
                'customer',
                'craft',
                'serve',
                'care'
            ])
            // ->whereDate('order_date', now())
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

                    $order->months_remaining = $diff->m;
                    $order->days_remaining = $diff->d;

                    $order->time_remaining = $diff->m . " Months " . $diff->d . " Days";
                }

                $order->range_start = $today->toDateTimeString();
                $order->range_end = $expiryDate->toDateTimeString();
            }
            return $order;
        });

        return response()->json($orders);
    }

    public function details($id)
    {
        $order = DB::table('order as o')
            ->join('customers as c', 'o.customer_id', '=', 'c.id')
            ->leftJoin('craft as cra', 'o.craft_id', '=', 'cra.id')
            ->leftJoin('serves as s', 'o.serve_id', '=', 's.id')
            ->leftJoin('care as ca', 'o.care_id', '=', 'ca.id')
            ->where('o.id', $id)
            ->select(
                'o.*',
                'c.full_name',
                'c.phone',
                'c.email',
                'c.address',
                'cra.name as craft_name',
                'cra.code as craft_code',
                'cra.fee as craft_fee',
                's.name as serve_name',
                's.code as serve_code',
                's.colour as serve_colour',
                's.fee as serve_fee',
                'ca.name as care_name',
                'ca.code as care_code',
                'ca.fee as care_fee',
            )
            ->first();

        return response()->json($order);
    }

    public function orderdetails($id) {
        $orderdetails = DB::table('order_details')
            ->join('products', 'order_details.pro_id', '=', 'products.id')
            ->leftJoin('order', 'order_details.order_id', '=', 'order.id')
            ->leftJoin('categories', 'products.cat_id', '=', 'categories.id')
            ->leftJoin('craft', 'order.craft_id', '=', 'craft.id')
            ->leftJoin('serves', 'order.serve_id', '=', 'serves.id')
            ->leftJoin('care', 'order.care_id', '=', 'care.id')
            ->where('order_details.order_id', $id)
            ->select(
                'products.product_name',
                'products.product_code',
                'products.image',
                'order_details.*',
                'categories.name as category_name',
                'craft.name as craft_name',
                'serves.name as serve_name',
                'care.name as care_name'
            )
            ->get();

        return response()->json($orderdetails);
    }

    public function updatecraft(Request $request, $id)
    {
        $request->validate([
            'categories_id' => 'required|exists:care,id',
        ]);

        $order = Order::findOrFail($id);

        $order->update([
            'craft_id' => $request->categories_id,
            'care_id' => $request->categories_id,
            'serve_id' => $request->categories_id,
        ]);

        return response()->json([
            'message' => 'Care updated successfully',
            'categories_id' => $request->categories_id,
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

    Public function updateApprove(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'approve' => 'required|string',
        ]);

        $approveValue = $request->approve === 'Approved' ? 1 : 0;

        $order = Order::findOrFail($id);

        $order->update([
            'approve' => $approveValue,
            'approved_at' => $approveValue ? now() : null,
        ]);

        return response()->json([
            'message' => 'Approval updated successfully',
            'approve' => $approveValue,
        ]);
    }
}
