<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $Brand=Brand::all();
        return response()->json($Brand);
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
           'name' =>'required|unique:Brand|max:255',
        ]);

            $Brand= new Brand;
            $Brand->name=$request->name;
            $Brand->code=$request->code;
            $Brand->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Brand  $Brand
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Brand=DB::table('Brand')->where('id',$id)->first();
        return response()->json($Brand);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Brand  $Brand
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        $Brand= Brand::find($id);
        $Brand->name=$request->name;

        $Brand->update();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Brand  $Brand
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $Brand = Brand::findOrFail($id);
        $Brand->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}
