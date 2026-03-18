<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Image;

class SuppliersController extends Controller
{
    public function index()
    {
        $suppliers = DB::table('suppliers')->get();
        return response()->json($suppliers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|unique:suppliers|max:255',
            'phone' => 'required|unique:suppliers|max:15',
            'supplier_id'   => 'required|exists:suppliers,id',
        ]);

        if ($request->hasFile('document')) {
            $validated['document'] = $request->file('document')
                ->store('suppliers', 'public');
        }

        try {

            $nextId = DB::table('suppliers')->max('id') + 1;
            $meetingNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
            $supplierId = 'QV-SUPP-' . $meetingNumber;

            $data = [
                'name' => $request->name,
                'supplier_id' => $supplierId,
                'email' => $request->email,
                'phone' => $request->phone,
                'shopname' => $request->shopname,
                'address' => $request->address,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($request->photo) {
                $position = strpos($request->photo, ';');
                $sub = substr($request->photo, 0, $position);
                $ext = explode('/', $sub)[1];
                $name = time() . '.' . $ext;
                $img = Image::make($request->photo)->resize(270, 270);

                $upload_path = 'backend/suppliers/';
                $image_url = $upload_path . $name;
                $img->save($image_url);

                $data['photo'] = '/' . $image_url;
            }

            $supplier = DB::table('suppliers')->insertGetId($data);

            return response()->json([
                'message' => 'Supplier registered successfully',
                'supplier_id' => $supplierId
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Supplier failed',
                'error'   => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Supplier created successfully',
            'supplier' => $supplier
        ], 201);
    }

    public function show($id)
    {
        $supplier = DB::table('suppliers')->where('id', $id)->first();

        if (!$supplier) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }

        return response()->json($supplier);
    }

    public function update(Request $request, $id)
    {
        $supplier = DB::table('suppliers')->where('id', $id)->first();

        if (!$supplier) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'shopname' => $request->shopname,
            'address' => $request->address,
            'updated_at' => now(),
        ];

        $image = $request->photo;
        $dbImg = $supplier->photo;

        if ($image != $dbImg && $image) {
            $position = strpos($image, ';');
            $sub = substr($image, 0, $position);
            $ext = explode('/', $sub)[1];
            $name = time() . '.' . $ext;
            $img = Image::make($image)->resize(270, 270);

            $upload_path = 'backend/suppliers/';
            $image_url = $upload_path . $name;
            $success = $img->save($image_url);

            if ($success) {
                // Delete old photo if exists
                if ($dbImg && file_exists(public_path($dbImg))) {
                    unlink(public_path($dbImg));
                }

                $data['photo'] = '/' . $image_url;
            }
        }

        DB::table('suppliers')->where('id', $id)->update($data);

        return response()->json(['message' => 'Supplier updated successfully']);
    }

    public function destroy($id)
    {
        $supplier = DB::table('suppliers')->where('id', $id)->first();

        if (!$supplier) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }

        // Delete photo if exists
        if ($supplier->photo && file_exists(public_path($supplier->photo))) {
            unlink(public_path($supplier->photo));
        }

        DB::table('suppliers')->where('id', $id)->delete();

        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
