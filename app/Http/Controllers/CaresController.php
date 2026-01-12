<?php

namespace App\Http\Controllers;

use App\Models\Care;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaresController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cares=Care::all();
        return response()->json($cares);
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
           'name' =>'required|unique:care|max:255',
        ]);

            $cares= new Care;
            $cares->name=$request->name;
            $cares->code=$request->code;
            $cares->fee=$request->fee;
            $cares->period=$request->period;
            $cares->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\care  $cares
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $cares=DB::table('care')->where('id',$id)->first();
        return response()->json($cares);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\care  $cares
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        $cares= Care::find($id);
        $cares->name=$request->name;
        $cares->code=$request->code;
        $cares->fee=$request->fee;
        $cares->period=$request->period;
        $cares->update();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\care  $cares
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $cares = Care::findOrFail($id);
        $cares->delete();

        return response()->json([
            'message' => 'Care deleted successfully',
        ]);
    }
}
