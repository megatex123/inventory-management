<?php

namespace App\Http\Controllers;

use App\Models\Products;
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
            'min_price' =>'nullable',
            'max_price' =>'nullable',
            'available' =>'nullable',
            'available_local' =>'nullable',
            'supplier_id' =>'nullable',
            'buying_date' =>'nullable',
            'product_qty' =>'nullable',
        ]);

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
            $products->min_price=$request->min_price;
            $products->max_price=$request->max_price;
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
            $products->min_price=$request->min_price;
            $products->max_price=$request->max_price;
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
    public function update(Request $request,$id)
    {

            $products= Products::find($id);
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
            $products->min_price=$request->min_price;
            $products->max_price=$request->max_price;
            $products->available=$request->available;
            $products->available_local=$request->available_local;
            $products->supplier_id=$request->supplier_id;
            $products->buying_date=$request->buying_date;
            $products->product_qty=$request->product_qty;

        $dbImg= $products->image;
        $image = '';
        if($image != $dbImg){

            $position=strpos($image,';');
            $sub= substr($image,0,$position);
            $ext= explode('/',$sub)[1];
            $name=time() . '.'.$ext;
            $img=Image::make($image)->resize(270,270);

            $upload_path='backend/products/';
            $image_url=$upload_path.$name;
            $success = $img->save($image_url);

            if($success){
                $products->image='/'.$image_url;
                $ming=ltrim($dbImg, $dbImg[0]);
               $done= unlink($ming);
               $products->update();
            }

        }else{
            $products->image=$image;
            $products->update();


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
