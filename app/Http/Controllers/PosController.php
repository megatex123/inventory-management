<?php

namespace App\Http\Controllers;

use App\products;
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
    ]);

    $cartProducts = DB::table('pos')->get();

    $categoryCount = [];
    foreach ($cartProducts as $product) {
        $prodCategoryId = DB::table('products')->where('id', $product->pro_id)->value('cat_id');
        if ($prodCategoryId) {
            $categoryCount[$prodCategoryId] = ($categoryCount[$prodCategoryId] ?? 0) + 1;
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

    if($request->total <= 7000.00 ){
        $categories_id = 1;
    }
    elseif($request->total > 10000.00){
        $categories_id = 3;
    }
    else{
        $categories_id = 2;
    }

    $nextId = DB::table('order')->max('id') + 1;
    $orderNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
    $orderId = 'ODR-' . $orderNumber;

    $data = [
        'order_id' => $orderId,
        'customer_id' => $request->customer_id,
        'qty' => $request->total_qty,
        'sub_total' => $request->total_amount,
        'total' => $request->total_amount,
        'order_date' => now(),
        'order_month' => date('F'),
        'order_year' => date('Y'),
        'craft_id' => $categories_id,
        'serve_id' => $categories_id,
        'care_id' => $categories_id,
        'is_reason' => $request->is_reason,
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
