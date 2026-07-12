<?php

namespace App\Http\Controllers;

use App\Models\CustomerProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CustomerProgressController extends Controller
{
    const ALLOWED_MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,csv,zip';

    public function index(Request $request)
    {
        $query = CustomerProgress::with(['customer', 'order']);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('full_name', 'LIKE', "%{$search}%")
                           ->orWhere('customer_id', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('order', function ($q2) use ($search) {
                        $q2->where('order_id', 'LIKE', "%{$search}%");
                    });
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
        $progress = CustomerProgress::with(['customer', 'order'])->find($id);

        if (!$progress) {
            return response()->json(['success' => false, 'message' => 'Progress entry not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $progress]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:' . implode(',', CustomerProgress::STATUSES),
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'updated_by' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:' . self::ALLOWED_MIMES . '|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $status = $request->status ?? 'pending';

        $data = [
            'customer_id' => $request->customer_id,
            'order_id' => $request->order_id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => $status,
            'progress_percentage' => $request->progress_percentage ?? ($status === 'completed' ? 100 : 0),
            'updated_by' => $request->updated_by,
            'completed_at' => $status === 'completed' ? now() : null,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $data['file_path'] = $file->store('customer_progress', 'public');
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_type'] = $file->getClientOriginalExtension();
            $data['file_size'] = $file->getSize();
        }

        $progress = CustomerProgress::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Progress entry created successfully',
            'data' => $progress->load(['customer', 'order']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $progress = CustomerProgress::find($id);

        if (!$progress) {
            return response()->json(['success' => false, 'message' => 'Progress entry not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:order,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:' . implode(',', CustomerProgress::STATUSES),
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'updated_by' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:' . self::ALLOWED_MIMES . '|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $status = $request->status ?? $progress->status;

        $progress->customer_id = $request->customer_id;
        $progress->order_id = $request->order_id;
        $progress->title = $request->title;
        $progress->description = $request->description;
        $progress->status = $status;
        $progress->progress_percentage = $request->progress_percentage ?? ($status === 'completed' ? 100 : $progress->progress_percentage);
        $progress->updated_by = $request->updated_by;

        // Stamp completed_at the moment status flips to completed; clear it if
        // reopened so a re-completion later reflects the real completion time.
        if ($status === 'completed' && !$progress->completed_at) {
            $progress->completed_at = now();
        } elseif ($status !== 'completed') {
            $progress->completed_at = null;
        }

        if ($request->hasFile('file')) {
            if ($progress->file_path) {
                Storage::disk('public')->delete($progress->file_path);
            }

            $file = $request->file('file');
            $progress->file_path = $file->store('customer_progress', 'public');
            $progress->file_name = $file->getClientOriginalName();
            $progress->file_type = $file->getClientOriginalExtension();
            $progress->file_size = $file->getSize();
        }

        $progress->save();

        return response()->json([
            'success' => true,
            'message' => 'Progress entry updated successfully',
            'data' => $progress->load(['customer', 'order']),
        ]);
    }

    public function destroy($id)
    {
        $progress = CustomerProgress::find($id);

        if (!$progress) {
            return response()->json(['success' => false, 'message' => 'Progress entry not found'], 404);
        }

        $progress->delete();

        return response()->json(['success' => true, 'message' => 'Progress entry deleted successfully']);
    }

    public function download($id)
    {
        $progress = CustomerProgress::find($id);

        if (!$progress || !$progress->file_path || !Storage::disk('public')->exists($progress->file_path)) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        return Storage::disk('public')->download($progress->file_path, $progress->file_name);
    }

    public function statistics()
    {
        $total = CustomerProgress::count();

        $byStatus = CustomerProgress::select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $avgProgress = CustomerProgress::whereNotIn('status', ['completed'])->avg('progress_percentage');

        return response()->json([
            'success' => true,
            'data' => [
                'total_entries' => $total,
                'by_status' => [
                    'pending' => $byStatus->get('pending', 0),
                    'in_progress' => $byStatus->get('in_progress', 0),
                    'completed' => $byStatus->get('completed', 0),
                    'on_hold' => $byStatus->get('on_hold', 0),
                ],
                'average_progress' => round((float) ($avgProgress ?? 0), 1),
            ],
        ]);
    }
}
