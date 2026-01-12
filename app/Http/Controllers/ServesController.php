<?php

namespace App\Http\Controllers;

use App\Models\Serves;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $serve=Serves::all();
        return response()->json($serve);
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
           'name' =>'required|unique:serve|max:255',
           'colour' =>'required',
           'code' =>'nullable',
           'description' => 'nullable',
        ]);

            $serve= new Serves;
            $serve->name=$request->name;
            $serve->code=$request->code;
            $serve->colour=$request->colour;
            $serve->fee=$request->fee;
            $serve->description=$request->description;

            $serve->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\serves  $serves
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $serve=DB::table('serves')->where('id',$id)->first();
        return response()->json($serve);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\serves  $serves
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        $serve= Serves::find($id);
        $serve->name=$request->name;
        $serve->code=$request->code;
        $serve->colour=$request->colour;
        $serve->fee=$request->fee;
        $serve->description=$request->description;


        $serve->update();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\serves  $serves
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $serve = Serves::findOrFail($id);
        $serve->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}
