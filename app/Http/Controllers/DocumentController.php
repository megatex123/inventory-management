<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    const ALLOWED_MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,csv,zip';

    public function index(Request $request)
    {
        $query = Document::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('file_name', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy($request->get('order_by', 'created_at'), $request->get('order_direction', 'desc'));

        $results = $query->paginate($request->get('per_page', 15));

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

    public function show($id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $document]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'uploaded_by' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:' . self::ALLOWED_MIMES . '|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        $document = Document::create([
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'uploaded_by' => $request->uploaded_by,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => $document,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'uploaded_by' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:' . self::ALLOWED_MIMES . '|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $document->title = $request->title;
        $document->description = $request->description;
        $document->category = $request->category;
        $document->uploaded_by = $request->uploaded_by;

        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($document->file_path);

            $file = $request->file('file');
            $document->file_path = $file->store('documents', 'public');
            $document->file_name = $file->getClientOriginalName();
            $document->file_type = $file->getClientOriginalExtension();
            $document->file_size = $file->getSize();
        }

        $document->save();

        return response()->json([
            'success' => true,
            'message' => 'Document updated successfully',
            'data' => $document,
        ]);
    }

    public function destroy($id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        $document->delete();

        return response()->json(['success' => true, 'message' => 'Document deleted successfully']);
    }

    public function download($id)
    {
        $document = Document::find($id);

        if (!$document || !Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['success' => false, 'message' => 'Document file not found'], 404);
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function categories()
    {
        $categories = Document::whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function statistics()
    {
        $total = Document::count();
        $totalSize = Document::sum('file_size');

        $byCategory = Document::select('category')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('category')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_documents' => $total,
                'total_size_bytes' => (int) $totalSize,
                'by_category' => $byCategory,
            ],
        ]);
    }
}
