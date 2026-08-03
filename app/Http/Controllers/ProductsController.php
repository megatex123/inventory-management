<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\Categories;
use App\Support\BusinessId;
use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Image;
class ProductsController extends Controller
{
    use FiltersSortsAndPaginates;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = DB::table('products')
            ->leftJoin('categories', 'products.cat_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as cat_name');

        $this->applyLikeFilter($query, $request, 'name', 'products.product_name');
        $this->applyLikeFilter($query, $request, 'code', 'products.product_code');
        $this->applyStartsWithFilter($query, $request, 'name_starts_with', 'products.product_name');
        $this->applyStartsWithFilter($query, $request, 'code_starts_with', 'products.product_code');
        $this->applyEqualsFilter($query, $request, 'category_id', 'products.cat_id');
        $this->applyYearMonthFilter($query, $request, 'products.created_at');
        $this->applyNumericRangeFilter($query, $request, 'products.price', 'min_price', 'max_price');

        $status = $request->input('status');
        if (is_scalar($status) && $status !== '') {
            if ($status === 'available') {
                $query->where('products.product_qty', '>=', 1);
            } elseif ($status === 'out') {
                $query->where(function ($q) {
                    $q->where('products.product_qty', '<', 1)->orWhereNull('products.product_qty');
                });
            }
        }

        $sortBy = $request->get('sort_by', 'product_name');
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, ['product_name', 'product_code', 'category', 'price', 'product_qty', 'created_at'], true)) {
            $sortBy = 'product_name';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        if ($sortBy === 'category') {
            // Sorting by the RELATED category's name requires the join --
            // cat_id on products is just a foreign id, not a name. leftJoin
            // (not innerJoin) so a product with a null/orphaned cat_id still
            // appears in results (0 such rows exist live today, but the
            // old query's innerJoin would have silently DROPPED them
            // entirely -- this is a proactive correctness improvement, not
            // just a refactor, matching the leftJoin pattern already
            // established for sub_category in Batch 5).
            $query->orderBy('categories.name', $sortDir);
        } elseif ($sortBy === 'price') {
            $query->orderByRaw('CAST(products.price AS DECIMAL(10,2)) ' . $sortDir);
        } else {
            $query->orderBy('products.' . $sortBy, $sortDir);
        }
        $query->orderBy('products.id', $sortDir);

        $perPage = $this->resolvePerPage($request);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    /**
     * All products, unpaginated, with the SAME query shape (both joins,
     * cat_name/sup_name fields, unfiltered, id DESC) as the pre-pagination
     * index() -- preserved byte-for-byte so existing bare-array consumers
     * (stock/index.vue, pos/index.vue, order/edit.vue) need zero logic
     * changes, only a URL change.
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return response()->json(
            DB::table('products')
                ->join('categories', 'products.cat_id', '=', 'categories.id')
                ->join('suppliers', 'products.supplier_id', '=', 'suppliers.id')
                ->select('categories.name as cat_name', 'suppliers.name as sup_name', 'products.*')
                ->orderBy('products.id', 'DESC')
                ->get()
        );
    }

    /**
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $nameStartingLetters = DB::table('products')
            ->selectRaw('DISTINCT UPPER(LEFT(product_name, 1)) as letter')
            ->whereNotNull('product_name')
            ->where('product_name', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $codeStartingLetters = DB::table('products')
            ->selectRaw('DISTINCT UPPER(LEFT(product_code, 1)) as letter')
            ->whereNotNull('product_code')
            ->where('product_code', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $availableYears = DB::table('products')
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->orderByDesc('year')
            ->pluck('year');

        return response()->json([
            'success' => true,
            'data' => [
                'name_starting_letters' => $nameStartingLetters,
                'code_starting_letters' => $codeStartingLetters,
                'available_years' => $availableYears,
            ],
        ]);
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
            'cat_id' =>'required|exists:categories,id',
            'brand_id' => 'nullable',
            'product_name' =>'required|unique:products|max:255',
            'price' =>'nullable',
            'price_updated_at' =>'nullable',
            'available' =>'nullable',
            'available_local' =>'nullable',
            'supplier_id' =>'nullable',
            'buying_date' =>'nullable',
            'product_qty' =>'nullable',
        ]);

        $validateData['category_name'] = $category_name;

        $category = Categories::findOrFail($request->cat_id);
        $productCode = BusinessId::next('products', 'product_code', $category->code . '-', 6);

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
            $products->product_code=$productCode;
            $products->cat_id=$request->cat_id;
            $products->brand_id=$request->brand_id;
            $products->product_name=$request->product_name;
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
            $products->product_code=$productCode;
            $products->cat_id=$request->cat_id;
            $products->brand_id=$request->brand_id;
            $products->product_name=$request->product_name;
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
