<?php

namespace App\Http\Controllers;

use App\Models\SubCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories=SubCategories::with('category')->get();
        // dd($categories);
        return response()->json($categories);
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
           'name' =>'required|unique:categories|max:255',
        ]);

            $categories= new SubCategories;
            $categories->cat_id=$request->cat_id;
            $categories->name=$request->name;
            $categories->code=$request->code;
            $categories->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\categories  $categories
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $subCategory = SubCategories::with('category')->findOrFail($id);
        return response()->json($subCategory);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\categories  $categories
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        $categories= SubCategories::find($id);
        $categories->cat_id=$request->cat_id;
        $categories->name=$request->name;
        $categories->code=$request->code;

        $categories->update();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\categories  $categories
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $categories = SubCategories::findOrFail($id);
        $categories->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}
