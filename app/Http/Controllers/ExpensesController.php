<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use App\Models\Expenses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ExpensesController extends Controller
{
    use FiltersSortsAndPaginates;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Expenses::query();

        $this->applyLikeFilter($query, $request, 'search', 'details');

        $this->resolveSortAndApply(
            $query,
            $request,
            ['details', 'amount', 'expenses_date', 'created_at'],
            'created_at',
            'id',
            [],
            'desc'
        );

        $perPage = $this->resolvePerPage($request);
        $paginator = $query->paginate($perPage);

        return $this->paginatedResponse($paginator);
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
            'details' =>'required',
            'amount' =>'required',
         ]);

             $categories= new Expenses;
             $categories->amount=$request->amount;
             $categories->details=$request->details;
             $categories->expenses_date=$request->date;
             $categories->save();

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\expenses  $expenses
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $expenses=DB::table('expenses')->where('id',$id)->first();
        return response()->json($expenses);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\expenses  $expenses
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {

        $expenses= expenses::find($id);
        $expenses->amount=$request->amount;
        $expenses->details=$request->details;
        $expenses->expenses_date=$request->expenses_date;

            $expenses->update();

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\expenses  $expenses
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        DB::table('expenses')->where('id',$id)->delete();
    }
}
