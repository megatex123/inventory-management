<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use App\Models\Salaries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalariesController extends Controller
{
    use FiltersSortsAndPaginates;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function paid(Request $request,$id){

        $validate = $request->validate([
            'salary_month'=>'required',
        ]);

        $month=$request->salary_month;


        $checkYear=date('Y');
        $check=DB::table('salaries')->where('emp_id',$id)->where('salary_month',$month)->where('salary_year',$checkYear)->first();
if($check){
    return response()->json('Salary Alrady Paid');
}else{

    $salaries= new Salaries;
    $salaries->emp_id=$id;
    $salaries->amount=$request->sallery;
    $salaries->salary_date=date('d/m/y');
    $salaries->salary_month=$month;
    $salaries->salary_year=date('Y');
    $salaries->save();
    return response()->json('Salary Paid Successfully');

}

    }


    public function salary(){
        $salary= DB::table('salaries')->select('salary_month')->groupBy('salary_month')->get();
        return response()->json($salary);
    }

public function salaryview(Request $request, $id){
    $query = DB::table('salaries')
        ->where('salary_month', $id)
        ->join('employees', 'salaries.emp_id', 'employees.id')
        ->select('employees.name', 'employees.phone', 'salaries.*');

    // The page's search box used to filter on salaries.salary_month --
    // a no-op, since this endpoint is already scoped to exactly one
    // month via the {id} route param (every row shares the same
    // value). Fixed to filter on the visible Name column instead.
    $this->applyLikeFilter($query, $request, 'search', 'employees.name');

    // salaries and employees both have an unqualified `created_at`
    // column, so an unqualified ORDER BY created_at is ambiguous SQL
    // once the two tables are joined -- confirmed live (see task-2
    // report). Rewrite the incoming sort_by so the allow-list entry
    // presented to the trait/frontend stays the plain 'created_at'
    // (matching the SortableTh sort-key and the allow-list documented
    // in the plan), while the column resolveSortAndApply actually
    // orders by is table-qualified.
    if ($request->get('sort_by') === 'created_at') {
        $request->merge(['sort_by' => 'salaries.created_at']);
    }

    // salary_date/salary_month/salary_year/emp_id are deliberately NOT
    // sortable here -- see the plan's Global Constraints for why.
    // amount is varchar(191) in the live schema, so it needs CAST for a
    // numeric (not lexicographic) sort.
    $this->resolveSortAndApply(
        $query,
        $request,
        ['name', 'phone', 'amount', 'salaries.created_at'],
        'salaries.id',
        'salaries.id',
        ['amount'],
        'desc'
    );

    $perPage = $this->resolvePerPage($request);
    $paginator = $query->paginate($perPage);

    return $this->paginatedResponse($paginator);
}
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\salaries  $salaries
     * @return \Illuminate\Http\Response
     */
    public function edit(salaries $salaries)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\salaries  $salaries
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, salaries $salaries)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\salaries  $salaries
     * @return \Illuminate\Http\Response
     */
    public function destroy(salaries $salaries)
    {
        //
    }
}
