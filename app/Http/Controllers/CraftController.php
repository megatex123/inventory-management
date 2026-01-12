<?php

namespace App\Http\Controllers;

use App\Models\Craft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CraftController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $craft=Craft::all();
        return response()->json($craft);
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
           'name' =>'required|unique:craft|max:255',
        ]);

            $craft= new Craft;
            $craft->name=$request->name;
            $craft->code=$request->code;
            $craft->fee=$request->fee;
            $craft->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\categories  $categories
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $craft=DB::table('craft')->where('id',$id)->first();
        return response()->json($craft);
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
        $craft= Craft::find($id);
        $craft->name=$request->name;
        $craft->code=$request->code;
        $craft->fee=$request->fee;

        $craft->update();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\categories  $categories
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $craft = Craft::findOrFail($id);
        $craft->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}
