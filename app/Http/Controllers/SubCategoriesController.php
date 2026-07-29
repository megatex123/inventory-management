<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use App\Models\SubCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubCategoriesController extends Controller
{
    use FiltersSortsAndPaginates;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = SubCategories::with('category');

        $this->applyLikeFilter($query, $request, 'name', 'sub_categories.name');
        $this->applyLikeFilter($query, $request, 'code', 'sub_categories.code');
        $this->applyStartsWithFilter($query, $request, 'name_starts_with', 'sub_categories.name');
        $this->applyStartsWithFilter($query, $request, 'code_starts_with', 'sub_categories.code');
        $this->applyEqualsFilter($query, $request, 'category_id', 'sub_categories.cat_id');
        $this->applyYearMonthFilter($query, $request, 'sub_categories.created_at');

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, ['name', 'code', 'category', 'created_at'], true)) {
            $sortBy = 'name';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        if ($sortBy === 'category') {
            // Sorting by the RELATED category's name requires a join --
            // cat_id on sub_categories is just a foreign id, not a name.
            // leftJoin (not innerJoin) so a sub_category with no matching
            // category (null/orphaned cat_id) still appears in the results.
            // select('sub_categories.*') keeps the join from polluting the
            // result columns (and from colliding categories.id with
            // sub_categories.id).
            $query->leftJoin('categories', 'sub_categories.cat_id', '=', 'categories.id')
                ->select('sub_categories.*')
                ->orderBy('categories.name', $sortDir);
            $query->orderBy('sub_categories.id', $sortDir);
        } else {
            $query->orderBy('sub_categories.' . $sortBy, $sortDir);
            $query->orderBy('sub_categories.id', $sortDir);
        }

        $perPage = $this->resolvePerPage($request);
        $results = $query->paginate($perPage);

        return $this->paginatedResponse($results);
    }

    /**
     * All sub-categories, unpaginated, for dropdown/lookup consumers.
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return response()->json(
            SubCategories::with('category')->orderBy('name')->get()
        );
    }

    /**
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $nameStartingLetters = SubCategories::selectRaw('DISTINCT UPPER(LEFT(name, 1)) as letter')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $codeStartingLetters = SubCategories::selectRaw('DISTINCT UPPER(LEFT(code, 1)) as letter')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $availableYears = SubCategories::selectRaw('DISTINCT YEAR(created_at) as year')
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
