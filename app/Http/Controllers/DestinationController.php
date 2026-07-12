<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DestinationController extends Controller
{
    public function index(Request $request)
    {
        $query = Destination::query();

        if ($request->filled('search')) {
            $query->where('description', 'LIKE', "%{$request->search}%");
        }

        $query->orderBy('description');

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255|unique:destination,description',
            'status' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $destination = Destination::create([
            'description' => $request->description,
            'status' => $request->status ?? 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Destination created successfully',
            'data' => $destination,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $destination = Destination::find($id);

        if (!$destination) {
            return response()->json(['success' => false, 'message' => 'Destination not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255|unique:destination,description,' . $id,
            'status' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $destination->update([
            'description' => $request->description,
            'status' => $request->status ?? $destination->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Destination updated successfully', 'data' => $destination]);
    }

    public function destroy($id)
    {
        $destination = Destination::find($id);

        if (!$destination) {
            return response()->json(['success' => false, 'message' => 'Destination not found'], 404);
        }

        $destination->delete();

        return response()->json(['success' => true, 'message' => 'Destination deleted successfully']);
    }
}
