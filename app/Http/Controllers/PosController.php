<?php

namespace App\Http\Controllers;

use App\products;
use App\Support\BusinessId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PosController extends Controller
{
    public function addCategoryToCart(Request $request)
{
    $products = DB::table('products')
        ->where('cat_id', $request->category_id)
        ->where('product_qty', '>', 0)
        ->get();

    foreach ($products as $product) {
        app('App\Http\Controllers\CartController')->addToCart($product->id);
    }

    return response()->json([
        'message' => 'All products from category added to cart'
    ]);
}
  public function catProduct($id){
    $catpro= DB::table('products')->where('cat_id',$id)->get();
    return response()->json($catpro);
  }


public function orderdone(Request $request)
{
    $validateData = $request->validate([
        'customer_id' => 'required',
        'total_qty' => 'required|integer',
        'total_amount' => 'required|numeric',
        'is_reason' => 'required|integer',
        'skip_quivicare' => 'nullable|boolean',
    ]);

    $cartProducts = DB::table('pos')->get();

    // QuiviCare RMA-eligible categories — CPU, SSD, GPU, HDD, RAM, MBD, PSU,
    // HSF, AIO. Must match OrderController::CARE_ELIGIBLE_CATEGORIES.
    $careCategories = [1, 2, 3, 4, 5, 6, 7, 8, 11];

    $categoryCount = [];
    $eligibleCareTotal = 0;
    foreach ($cartProducts as $product) {
        $prodCategoryId = DB::table('products')->where('id', $product->pro_id)->value('cat_id');
        if ($prodCategoryId) {
            $categoryCount[$prodCategoryId] = ($categoryCount[$prodCategoryId] ?? 0) + 1;
            if (in_array($prodCategoryId, $careCategories)) {
                $eligibleCareTotal += $product->sub_total;
            }
        }
    }

    $mainCategoryId = null;
    $max = 0;
    foreach ($categoryCount as $catId => $count) {
        if ($count > $max) {
            $max = $count;
            $mainCategoryId = $catId;
        }
    }

    // QuiviCraft build-class tier — RM0-6999 BASIC, RM7000-9999 MEDIUM,
    // RM10000-19999 PREMIUM, RM20000+ ULTRA. Boundaries are inclusive on both
    // ends (no RM7000.00-exactly gap), and mapped to the actual craft table
    // IDs (1=BASIC, 2=PREMIUM, 3=MEDIUM, 4=ULTRA — PREMIUM/MEDIUM are NOT in
    // numeric order in that table).
    // Reads total_amount (the validated request field) — $request->total does
    // not exist on this request and silently evaluated to null/0 previously,
    // which is why every order was landing on BASIC regardless of its real total.
    if ($request->total_amount <= 6999.00) {
        $craftId = 1; // BASIC
    } elseif ($request->total_amount <= 9999.00) {
        $craftId = 3; // MEDIUM
    } elseif ($request->total_amount <= 19999.00) {
        $craftId = 2; // PREMIUM
    } else {
        $craftId = 4; // ULTRA
    }

    // QuiviServe tier — < RM7,000 Essential Kit, RM7,000-9,999 Prime Series,
    // >= RM10,000 Collector's Edition (serves table IDs 1/2/3 already in that
    // order).
    if ($request->total_amount < 7000.00) {
        $serveTierId = 1; // Essential Kit
    } elseif ($request->total_amount < 10000.00) {
        $serveTierId = 2; // Prime Series
    } else {
        $serveTierId = 3; // Collector's Edition
    }

    // QuiviCare tier — based on the sum of RMA-eligible parts only
    // ($eligibleCareTotal, computed above), not the whole order total.
    // care table IDs are 1=COR3, 2=RI5E, 3=VIS10N (already in ascending order).
    if ($eligibleCareTotal <= 6999.00) {
        $careTierId = 1; // COR3
    } elseif ($eligibleCareTotal <= 9999.00) {
        $careTierId = 2; // RI5E
    } else {
        $careTierId = 3; // VIS10N
    }

    $orderId = BusinessId::next('order', 'order_id', 'QV-BLDP-', 6);

    $craftTagId = BusinessId::next('order', 'craft_tag_id', 'BLDP-DRF-', 6) . '-01';

    $craftDataId = BusinessId::next('order', 'craft_data_id', 'QV-CRFT-', 6);
    $data = [
        'order_id' => $orderId,
        'customer_id' => $request->customer_id,
        'qty' => $request->total_qty,
        'sub_total' => $request->total_amount,
        'total' => $request->total_amount,
        'order_date' => now(),
        'order_month' => date('F'),
        'order_year' => date('Y'),
        'craft_id' => $craftId,
        'serve_id' => $serveTierId,
        'care_id' => $careTierId,
        'is_reason' => $request->is_reason,
        'craft_tag_id' => $craftTagId,
        'skip_quivicare' => $request->boolean('skip_quivicare'),
        'craft_data_id' => $craftDataId,
    ];

    $order_id = DB::table('order')->insertGetId($data);

    // Save order details
    foreach ($cartProducts as $cart) {
        DB::table('order_details')->insert([
            'order_id' => $order_id,
            'pro_id' => $cart->pro_id,
            'pro_qty' => $cart->pro_qty,
            'pro_price' => $cart->pro_price,
            'sub_total' => $cart->sub_total
        ]);

        // Update product stock
        DB::table('products')->where('id', $cart->pro_id)
            ->update(['product_qty' => DB::raw('product_qty - ' . $cart->pro_qty)]);
    }

    // Clear pos table
    DB::table('pos')->delete();

    return response()->json([
        'success' => true,
        'message' => 'Order placed successfully',
        'order_id' => $orderId
    ]);
}


public function todaySell(){
    $date=date('d/m/Y');
    $sell= DB::table('order')->where('order_date',$date)->sum('total');
    return response()->json($sell);
}
public function todayexp(){
    $date=date('Y-m-d');
    $exp= DB::table('expenses')->where('expenses_date',$date)->sum('amount');
    return response()->json($exp);
}
public function todaystock(){
    $pro= DB::table('products')->where('product_qty','<','1')->get();
    return response()->json($pro);
}
}
