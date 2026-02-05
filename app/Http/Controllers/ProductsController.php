<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\Categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Image;
class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       $products=DB::table('products')
                ->join('categories', 'products.cat_id','categories.id')
                ->join('suppliers', 'products.supplier_id','suppliers.id')
                ->select('categories.name as cat_name','suppliers.name as sup_name','products.*')
                ->orderBy('products.id','DESC')
                ->get();
                return response()->json($products);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $category_name = Categories::where('id',$request->cat_id)->pluck('name')->first();
        $validateData=$request->validate([
            'product_code' =>'required|unique:products|max:255',
            'cat_id' =>'required',
            'brand_id' => 'nullable',
            'product_name' =>'required|unique:products|max:255',
            'capacity' =>'nullable',
            'form' =>'nullable',
            'interface' =>'nullable',
            'read_speed' =>'nullable',
            'write_speed' =>'nullable',
            'price_tier' =>'nullable',
            'price' =>'nullable',
            'price_updated_at' =>'nullable',
            'available' =>'nullable',
            'available_local' =>'nullable',
            'supplier_id' =>'nullable',
            'buying_date' =>'nullable',
            'product_qty' =>'nullable',
        ]);

        $validateData['category_name'] = $category_name;

        if($request->photo){
            $position=strpos($request->photo,';');
            $sub= substr($request->photo,0,$position);
            $ext= explode('/',$sub)[1];
            $name=time() . '.'.$ext;
            $img=Image::make($request->photo)->resize(270,270);

            $upload_path='backend/products/';
            $image_url=$upload_path.$name;
            $img->save($image_url);

            $products= new Products;
            $products->product_code=$request->product_code;
            $products->cat_id=$request->cat_id;
            $products->brand_id=$request->brand_id;
            $products->product_name=$request->product_name;
            $products->capacity=$request->capacity;
            $products->form=$request->form;
            $products->interface=$request->interface;
            $products->read=$request->read;
            $products->write=$request->write;
            $products->tier=$request->tier;
            $products->price=$request->price;
            $products->price_updated_at=$request->price_updated_at;
            $products->available=$request->available;
            $products->available_local=$request->available_local;
            $products->supplier_id=$request->supplier_id;
            $products->buying_date=$request->buying_date;
            $products->product_qty=$request->product_qty;
            $products->image='/'.$image_url;
            $products->save();
        }else{
            $products= new Products;
            $products->product_code=$request->product_code;
            $products->cat_id=$request->cat_id;
            $products->brand_id=$request->brand_id;
            $products->product_name=$request->product_name;
            $products->capacity=$request->capacity;
            $products->form=$request->form;
            $products->interface=$request->interface;
            $products->read=$request->read;
            $products->write=$request->write;
            $products->tier=$request->tier;
            $products->price=$request->price;
            $products->price_updated_at=$request->price_updated_at;
            $products->available=$request->available;
            $products->available_local=$request->available_local;
            $products->supplier_id=$request->supplier_id;
            $products->buying_date=$request->buying_date;
            $products->product_qty=$request->product_qty;
            $products->save();
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\products  $products
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $products=DB::table('products')->where('id',$id)->first();
        return response()->json($products);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\products  $products
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            // Get the product
            $product = Products::findOrFail($id);

            // Get category name
            $category_name = Categories::where('id', $request->cat_id)->value('name');

            // Validate the request data
            $validateData = $request->validate([
                'product_code' => 'sometimes|required|max:255|unique:products,product_code,' . $id,
                'cat_id' => 'sometimes|required|exists:categories,id',
                'brand_id' => 'nullable|max:255',
                'product_name' => 'sometimes|required|max:255|unique:products,product_name,' . $id,
                'capacity' => 'nullable|max:255',
                'form' => 'nullable|max:255',
                'interface' => 'nullable|max:255',
                'read' => 'nullable|max:255',
                'write' => 'nullable|max:255',
                'tier' => 'nullable|max:255',
                'price' => 'nullable|numeric',
                'price_updated_at' =>'nullable',
                'available' => 'nullable|max:255',
                'available_local' => 'nullable|max:255',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'buying_date' => 'nullable|date',
                'product_qty' => 'nullable|integer|min:0',
                'buying_price' => 'nullable|numeric|min:0',
                'selling_price' => 'nullable|numeric|min:0',
                'root' => 'nullable|max:255',
            ]);

            // Add category name to validated data
            $validateData['category_name'] = $category_name;

            // Handle image update
            if ($request->has('image') && !empty($request->image) && $request->image != 'null') {
                // Check if it's a new base64 image
                if (strpos($request->image, 'data:image') === 0) {
                    $position = strpos($request->image, ';');
                    $sub = substr($request->image, 0, $position);
                    $ext = explode('/', $sub)[1];
                    $name = time() . '.' . $ext;

                    // Create image using Image Intervention
                    $img = Image::make($request->image)->resize(270, 270);

                    $upload_path = 'backend/products/';
                    $image_url = $upload_path . $name;

                    // Save new image
                    $img->save($image_url);

                    // Delete old image if exists
                    if ($product->image && file_exists(public_path(ltrim($product->image, '/')))) {
                        unlink(public_path(ltrim($product->image, '/')));
                    }

                    $validateData['image'] = '/' . $image_url;
                }
            }

            // Update the product
            $product->update($validateData);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function stockupdate(Request $request,$id){
        $products= Products::find($id);
        $products->product_qty=$request->product_qty;

            $products->update();
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\products  $products
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $products = DB::table('products')->where('id',$id)->first();
        $photo= $products->image;
        if($photo){
         $ming=ltrim($photo, $photo[0]);
            unlink($ming);
            DB::table('products')->where('id',$id)->delete();
        }else{
          DB::table('products')->where('id',$id)->delete();

        }
    }
}
