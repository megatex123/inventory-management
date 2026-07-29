<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use App\Models\Categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriesController extends Controller
{
    use FiltersSortsAndPaginates;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Categories::query();

        $this->applyLikeFilter($query, $request, 'name', 'name');
        $this->applyLikeFilter($query, $request, 'code', 'code');
        $this->applyStartsWithFilter($query, $request, 'name_starts_with', 'name');
        $this->applyStartsWithFilter($query, $request, 'code_starts_with', 'code');
        $this->applyYearMonthFilter($query, $request);
        $this->resolveSortAndApply($query, $request, ['name', 'code', 'created_at'], 'name');

        $perPage = $this->resolvePerPage($request);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    /**
     * All categories, unpaginated, for dropdown/lookup consumers.
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return response()->json(Categories::orderBy('name')->get());
    }

    /**
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $nameStartingLetters = Categories::selectRaw('DISTINCT UPPER(LEFT(name, 1)) as letter')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $codeStartingLetters = Categories::selectRaw('DISTINCT UPPER(LEFT(code, 1)) as letter')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $availableYears = Categories::selectRaw('DISTINCT YEAR(created_at) as year')
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
           'name' =>'required|unique:categories|max:255',
        ]);

            $categories= new Categories;
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
        $categories=DB::table('categories')->where('id',$id)->first();
        return response()->json($categories);
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
        $categories= Categories::find($id);
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
        $categories = Categories::findOrFail($id);
        $categories->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}
