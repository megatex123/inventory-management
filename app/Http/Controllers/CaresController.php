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
    public function index(Request $request)
    {
        $query = Care::query();

        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->name . '%');
        }

        if ($request->filled('code')) {
            $query->where('code', 'LIKE', '%' . $request->code . '%');
        }

        if ($request->filled('fee')) {
            $query->where('fee', 'LIKE', '%' . $request->fee . '%');
        }

        if ($request->filled('name_starts_with')) {
            $query->where('name', 'LIKE', $request->name_starts_with . '%');
        }

        if ($request->filled('code_starts_with')) {
            $query->where('code', 'LIKE', $request->code_starts_with . '%');
        }

        if ($request->filled('min_fee')) {
            $query->whereRaw('CAST(fee AS DECIMAL(10,2)) >= ?', [(float) $request->min_fee]);
        }

        if ($request->filled('max_fee')) {
            $query->whereRaw('CAST(fee AS DECIMAL(10,2)) <= ?', [(float) $request->max_fee]);
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);

            if ($request->filled('month')) {
                $query->whereMonth('created_at', $request->month);
            }
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, ['name', 'code', 'fee', 'created_at'], true)) {
            $sortBy = 'name';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        if ($sortBy === 'fee') {
            $query->orderByRaw('CAST(fee AS DECIMAL(10,2)) ' . $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }
        $query->orderBy('id', $sortDir);

        $perPage = min(max((int) $request->get('per_page', 10), 1), 100);
        $results = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    /**
     * All care tiers, unpaginated, for dropdown/lookup consumers.
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return response()->json(Care::orderBy('name')->get());
    }

    /**
     * Distinct filter option values computed across the whole table.
     *
     * @return \Illuminate\Http\Response
     */
    public function filterOptions()
    {
        $nameStartingLetters = Care::selectRaw('DISTINCT UPPER(LEFT(name, 1)) as letter')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $codeStartingLetters = Care::selectRaw('DISTINCT UPPER(LEFT(code, 1)) as letter')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('letter')
            ->pluck('letter');

        $availableYears = Care::selectRaw('DISTINCT YEAR(created_at) as year')
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
