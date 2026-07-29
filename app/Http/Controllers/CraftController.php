<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use App\Models\Craft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CraftController extends Controller
{
    use FiltersSortsAndPaginates;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Craft::query();

        $this->applyLikeFilter($query, $request, 'name', 'name');
        $this->applyLikeFilter($query, $request, 'code', 'code');
        $this->applyLikeFilter($query, $request, 'fee', 'fee');
        $this->applyStartsWithFilter($query, $request, 'name_starts_with', 'name');
        $this->applyStartsWithFilter($query, $request, 'code_starts_with', 'code');
        $this->applyYearMonthFilter($query, $request);
        $this->applyNumericRangeFilter($query, $request, 'fee', 'min_fee', 'max_fee');
        $this->resolveSortAndApply($query, $request, ['name', 'code', 'fee', 'created_at'], 'name', 'id', ['fee']);

        $perPage = $this->resolvePerPage($request);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    /**
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $nameStartingLetters = Craft::selectRaw('DISTINCT UPPER(LEFT(name, 1)) as letter')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $codeStartingLetters = Craft::selectRaw('DISTINCT UPPER(LEFT(code, 1)) as letter')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $availableYears = Craft::selectRaw('DISTINCT YEAR(created_at) as year')
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
