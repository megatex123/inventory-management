<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersSortsAndPaginates;
use App\Models\Employees;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Image;
class EmployeesController extends Controller
{
    use FiltersSortsAndPaginates;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Employees::query();

        if (is_scalar($request->input('search')) && $request->input('search') !== '') {
            $keyword = (string) $request->input('search');
            $escaped = addcslashes($keyword, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('phone', 'LIKE', '%' . $escaped . '%')
                    ->orWhere('email', 'LIKE', '%' . $escaped . '%');
            });
        }

        $this->resolveSortAndApply(
            $query,
            $request,
            ['name', 'phone', 'join_date', 'created_at'],
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
            // 'name' =>'required|unique:employees|max:255',
            'email' =>'required|unique:employees|max:255',
            'phone' =>'required|unique:employees|max:15',
            'nid' =>'required|unique:employees|max:50',
        ]);

        if($request->photo){
            $position=strpos($request->photo,';');
            $sub= substr($request->photo,0,$position);
            $ext= explode('/',$sub)[1];
            $name=time() . '.'.$ext;
            $img=Image::make($request->photo)->resize(270,270);

            $upload_path='backend/employees/';
            $image_url=$upload_path.$name;
            $img->save($image_url);

            $employees= new Employees;
            $employees->name=$request->name;
            $employees->email=$request->email;
            $employees->phone=$request->phone;
            $employees->nid=$request->nid;
            $employees->address=$request->address;
            $employees->sallery=$request->sallery;
            $employees->join_date=$request->join_date;
            $employees->photo='/'.$image_url;
            $employees->save();
        }else{
            $employees= new Employees;
            $employees->name=$request->name;
            $employees->email=$request->email;
            $employees->phone=$request->phone;
            $employees->nid=$request->nid;
            $employees->address=$request->address;
            $employees->sallery=$request->sallery;
            $employees->join_date=$request->join_date;
            $employees->save();
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\employees  $employees
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $employees=DB::table('employees')->where('id',$id)->first();
      return response()->json($employees);
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\employees  $employees
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        $employees= Employees::find($id);
        $employees->name=$request->name;
        $employees->email=$request->email;
        $employees->phone=$request->phone;
        $employees->nid=$request->nid;
        $employees->address=$request->address;
        $employees->sallery=$request->sallery;
        $employees->join_date=$request->join_date;
        $image=$request->photo;
        // $dbData= employees::find($id);

        $dbImg= $employees->photo;

        if($image != $dbImg){

            $position=strpos($image,';');
            $sub= substr($image,0,$position);
            $ext= explode('/',$sub)[1];
            $name=time() . '.'.$ext;
            $img=Image::make($image)->resize(270,270);

            $upload_path='backend/employees/';
            $image_url=$upload_path.$name;
            $success = $img->save($image_url);

            if($success){
                $employees->photo='/'.$image_url;
                if ($dbImg) {
                    $ming = ltrim($dbImg, $dbImg[0]);
                    if (file_exists(public_path($ming))) {
                        unlink(public_path($ming));
                    }
                }
               $employees->update();
            }

        }else{
            $employees->photo=$image;
            $employees->update();


        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\employees  $employees
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      $employees = DB::table('employees')->where('id',$id)->first();
      $photo= $employees->photo;
      if($photo){
       $ming=ltrim($photo, $photo[0]);
          if (file_exists(public_path($ming))) {
              unlink(public_path($ming));
          }
      }
      DB::table('employees')->where('id',$id)->delete();
    }
}
