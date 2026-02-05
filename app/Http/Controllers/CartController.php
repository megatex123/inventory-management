<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function addCategoryToCart(Request $request)
    {
        $products = DB::table('products')
            ->where('cat_id', $request->category_id)
            ->where('product_qty', '>', 0)
            ->get();

        foreach ($products as $product) {

            $check = DB::table('pos')->where('pro_id', $product->id)->first();

            if ($check) {
                DB::table('pos')
                    ->where('pro_id', $product->id)
                    ->increment('pro_qty');

                $updated = DB::table('pos')->where('pro_id', $product->id)->first();
                $subtotal = $updated->pro_qty * $updated->pro_price;

                DB::table('pos')->where('pro_id', $product->id)->update([
                    'sub_total' => $subtotal
                ]);
            } else {
                DB::table('pos')->insert([
                    'pro_id'     => $product->id,
                    'pro_name'   => $product->product_name,
                    'pro_qty'    => 1,
                    'pro_price'  => $product->selling_price,
                    'sub_total'  => $product->selling_price,
                ]);
            }
        }

        return response()->json([
            'message' => 'All products from category added to cart'
        ]);
    }

public function addcart(Request $request, $id)
{
    DB::beginTransaction();

    try {
        $product = DB::table('products')->where('id', $id)->first();

        if (!$product) {
            return response()->json('Product not found', 404);
        }

        if ($product->product_qty < 1) {
            return response()->json('Stock Out', 400);
        }

        $cart = DB::table('pos')->where('pro_id', $id)->first();

        if ($cart) {
            if ($cart->pro_qty >= $product->product_qty) {
                return response()->json('Stock Limit Reached', 400);
            }

            DB::table('pos')
                ->where('pro_id', $id)
                ->increment('pro_qty', 1);

            DB::table('pos')
                ->where('pro_id', $id)
                ->update([
                    'sub_total' => DB::raw('pro_qty * pro_price')
                ]);

        } else {
            DB::table('pos')->insert([
                'pro_id'    => $id,
                'pro_name'  => $product->product_name,
                'pro_qty'   => 1,
                'pro_price' => $product->price,
                'sub_total' => $product->price,
            ]);
        }

        DB::table('products')
            ->where('id', $id)
            ->decrement('product_qty');

        DB::commit();

        return response()->json('Cart Updated');

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


// public function getCart(){
//     $cart=DB::table('pos')->get();
//     return response()->json($cart);
// }

public function getCart()
{
    $cart = DB::table('pos')
        ->leftJoin('products', 'pos.pro_id', '=', 'products.id')
        ->leftJoin('categories', 'products.cat_id', '=', 'categories.id')
        ->select(
            'pos.id as cart_id',
            'pos.pro_id',
            'pos.pro_name',
            'pos.pro_qty',
            'pos.pro_price',
            'pos.sub_total',
            'products.id as product_id',
            'products.product_name',
            'products.product_qty',
            'products.image',
            'categories.id as category_id',
            'categories.name as category_name'
        )
        ->get();

    return response()->json($cart);
}


public function cartRemove($id)
{
    DB::beginTransaction();

    try {
        $cart = DB::table('pos')->where('id', $id)->first();

        if (!$cart) {
            return response()->json('Cart item not found', 404);
        }
        DB::table('products')
            ->where('id', $cart->pro_id)
            ->increment('product_qty', $cart->pro_qty);
        DB::table('pos')->where('id', $id)->delete();

        DB::commit();

        return response()->json('Cart item removed');

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function cartInc($id)
{
    $cart = DB::table('pos')->where('id', $id)->first();

    if (!$cart) {
        return response()->json('Cart item not found', 404);
    }

    $product = DB::table('products')->where('id', $cart->pro_id)->first();

    if (!$product || $product->product_qty < 1) {
        return response()->json('Stock Out', 400);
    }

    DB::transaction(function () use ($cart) {
        DB::table('pos')
            ->where('id', $cart->id)
            ->increment('pro_qty', 1);

        DB::table('pos')
            ->where('id', $cart->id)
            ->update([
                'sub_total' => DB::raw('pro_qty * pro_price')
            ]);

        DB::table('products')
            ->where('id', $cart->pro_id)
            ->decrement('product_qty');
    });

    return response()->json('Quantity Increased');
}

    public function cartDec($id)
{
    $cart = DB::table('pos')->where('id', $id)->first();

    if (!$cart) {
        return response()->json('Cart item not found', 404);
    }

    if ($cart->pro_qty <= 1) {
        return response()->json('Minimum quantity reached', 400);
    }

    DB::transaction(function () use ($cart) {
        DB::table('pos')
            ->where('id', $cart->id)
            ->decrement('pro_qty', 1);

        DB::table('pos')
            ->where('id', $cart->id)
            ->update([
                'sub_total' => DB::raw('pro_qty * pro_price')
            ]);

        DB::table('products')
            ->where('id', $cart->pro_id)
            ->increment('product_qty');
    });

    return response()->json('Quantity Decreased');
}
    /**
     * Display the specified resource.
     *
     * @param  \App\cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function show(cart $cart)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function edit(cart $cart)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, cart $cart)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function destroy(cart $cart)
    {
        //
    }
}
