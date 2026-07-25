<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesPhotoEvidence;
use App\Models\CraftInspection;
use App\Models\CraftInspectionItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CraftInspectionController extends Controller
{
    use ValidatesPhotoEvidence;
    // Component types matching the QuiviCraft Build Report checklist
    const COMPONENT_TYPES = ['cpu', 'mbd', 'gpu', 'ram', 'ssd', 'hdd', 'aio', 'hsf', 'psu', 'cse', 'fan', 'acc'];

    // "Good" value per gated group — anything else requires a note instead of photos
    const GOOD_VALUES = [
        'inspection' => 'sound',
        'packaging' => 'intact',
        'condition' => 'sound_pristine',
    ];

    public function show($orderId, $round = 1)
    {
        $order = Order::with(['customer', 'craft'])->find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $inspection = CraftInspection::firstOrCreate(
            ['order_id' => $orderId, 'round' => $round],
            ['status' => 'draft']
        );

        $inspection->load(['items' => function ($q) {
            $q->orderBy('created_at');
        }]);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'inspection' => $inspection,
                'component_types' => self::COMPONENT_TYPES,
                'phase_label' => 'Studio Inspection',
            ],
        ]);
    }

    public function storeItem(Request $request, $orderId, $round = 1)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'component_type' => 'required|string|in:' . implode(',', self::COMPONENT_TYPES),
            'order_detail_id' => 'nullable|integer|exists:order_details,id',
            'fields' => 'nullable|array',
            'model_verified' => 'boolean',
            'serial_recorded' => 'boolean',
            'factory_seal' => 'boolean',
            'qc_pass' => 'boolean',
            'inspection_status' => 'required|in:sound,not_sound',
            'inspection_note' => 'nullable|string|max:1000',
            'inspection_photos.*' => 'nullable|image|max:5120',
            'packaging_status' => 'required|in:intact,damaged',
            'packaging_note' => 'nullable|string|max:1000',
            'packaging_photos.*' => 'nullable|image|max:5120',
            'condition_status' => 'required|in:sound_pristine,issue',
            'condition_note' => 'nullable|string|max:1000',
            'condition_photos.*' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $groupErrors = $this->validateGroups($request, []);
        if ($groupErrors) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $groupErrors], 422);
        }

        DB::beginTransaction();
        try {
            $inspection = CraftInspection::firstOrCreate(
                ['order_id' => $orderId, 'round' => $round],
                ['status' => 'draft']
            );

            $item = CraftInspectionItem::create([
                'craft_inspection_id' => $inspection->id,
                'component_type' => $request->component_type,
                'order_detail_id' => $request->order_detail_id,
                'fields' => $request->fields ?? [],
                'model_verified' => $request->boolean('model_verified'),
                'serial_recorded' => $request->boolean('serial_recorded'),
                'factory_seal' => $request->boolean('factory_seal'),
                'qc_pass' => $request->boolean('qc_pass'),
                'inspection_status' => $request->inspection_status,
                'inspection_note' => $request->inspection_note,
                'inspection_photos' => $this->storePhotos($request, 'inspection_photos'),
                'packaging_status' => $request->packaging_status,
                'packaging_note' => $request->packaging_note,
                'packaging_photos' => $this->storePhotos($request, 'packaging_photos'),
                'condition_status' => $request->condition_status,
                'condition_note' => $request->condition_note,
                'condition_photos' => $this->storePhotos($request, 'condition_photos'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inspection item saved successfully',
                'data' => $item,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to save inspection item', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateItem(Request $request, $orderId, $round, $itemId)
    {
        $item = CraftInspectionItem::whereHas('craftInspection', function ($q) use ($orderId, $round) {
            $q->where('order_id', $orderId)->where('round', $round);
        })->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Inspection item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'fields' => 'nullable|array',
            'model_verified' => 'boolean',
            'serial_recorded' => 'boolean',
            'factory_seal' => 'boolean',
            'qc_pass' => 'boolean',
            'inspection_status' => 'required|in:sound,not_sound',
            'inspection_note' => 'nullable|string|max:1000',
            'inspection_photos.*' => 'nullable|image|max:5120',
            'remove_inspection_photos' => 'nullable|array',
            'packaging_status' => 'required|in:intact,damaged',
            'packaging_note' => 'nullable|string|max:1000',
            'packaging_photos.*' => 'nullable|image|max:5120',
            'remove_packaging_photos' => 'nullable|array',
            'condition_status' => 'required|in:sound_pristine,issue',
            'condition_note' => 'nullable|string|max:1000',
            'condition_photos.*' => 'nullable|image|max:5120',
            'remove_condition_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $groupErrors = $this->validateGroups($request, $item->only(['inspection_photos', 'packaging_photos', 'condition_photos']));
        if ($groupErrors) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $groupErrors], 422);
        }

        try {
            $item->fields = $request->fields ?? $item->fields;
            $item->model_verified = $request->boolean('model_verified');
            $item->serial_recorded = $request->boolean('serial_recorded');
            $item->factory_seal = $request->boolean('factory_seal');
            $item->qc_pass = $request->boolean('qc_pass');

            $item->inspection_status = $request->inspection_status;
            $item->inspection_note = $request->inspection_note;
            $item->inspection_photos = $this->mergePhotos($item->inspection_photos, $request, 'inspection_photos', 'remove_inspection_photos');

            $item->packaging_status = $request->packaging_status;
            $item->packaging_note = $request->packaging_note;
            $item->packaging_photos = $this->mergePhotos($item->packaging_photos, $request, 'packaging_photos', 'remove_packaging_photos');

            $item->condition_status = $request->condition_status;
            $item->condition_note = $request->condition_note;
            $item->condition_photos = $this->mergePhotos($item->condition_photos, $request, 'condition_photos', 'remove_condition_photos');

            $item->save();

            return response()->json([
                'success' => true,
                'message' => 'Inspection item updated successfully',
                'data' => $item->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update inspection item', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroyItem($orderId, $round, $itemId)
    {
        $item = CraftInspectionItem::whereHas('craftInspection', function ($q) use ($orderId, $round) {
            $q->where('order_id', $orderId)->where('round', $round);
        })->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Inspection item not found'], 404);
        }

        try {
            $item->delete();
            return response()->json(['success' => true, 'message' => 'Inspection item deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete inspection item', 'error' => $e->getMessage()], 500);
        }
    }

    public function complete($orderId, $round = 1)
    {
        $inspection = CraftInspection::where('order_id', $orderId)->where('round', $round)->first();

        if (!$inspection) {
            return response()->json(['success' => false, 'message' => 'Inspection not found'], 404);
        }

        $inspection->status = 'completed';
        $inspection->save();

        return response()->json([
            'success' => true,
            'message' => 'Inspection marked as completed',
            'data' => $inspection,
        ]);
    }

    public function statistics()
    {
        $total = CraftInspection::count();
        $draftCount = CraftInspection::where('status', 'draft')->count();
        $completedCount = CraftInspection::where('status', 'completed')->count();

        $pending = CraftInspection::with(['order.customer'])
            ->where('status', 'draft')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($inspection) {
                return [
                    'id' => $inspection->id,
                    'order_pk' => $inspection->order_id,
                    'order_code' => optional($inspection->order)->order_id,
                    'customer' => optional(optional($inspection->order)->customer)->full_name,
                    'round' => $inspection->round,
                    'status' => $inspection->status,
                    'updated_at' => $inspection->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'draft' => $draftCount,
                'completed' => $completedCount,
                'pending' => $pending,
            ],
        ]);
    }

    // Enforce: "good" status requires 1-2 photos; the "bad" status always requires
    // a note, and optionally allows up to 2 supporting photos too.
    private function validateGroups(Request $request, array $existingPhotos)
    {
        $errors = [];

        foreach (self::GOOD_VALUES as $group => $goodValue) {
            $status = $request->input("{$group}_status");
            $newPhotos = $request->file("{$group}_photos") ?? [];
            $newPhotoCount = is_array($newPhotos) ? count($newPhotos) : ($newPhotos ? 1 : 0);
            $removePhotos = $request->input("remove_{$group}_photos", []);
            $existingCount = max(0, count($existingPhotos["{$group}_photos"] ?? []) - count($removePhotos));

            $groupErrors = $this->validatePhotoEvidence($status, $goodValue, $request->input("{$group}_note"), $existingCount, $newPhotoCount);
            foreach ($groupErrors as $key => $messages) {
                $errors["{$group}_{$key}"] = $messages;
            }
        }

        return $errors;
    }

    private function storePhotos(Request $request, $field)
    {
        if (!$request->hasFile($field)) {
            return [];
        }

        $paths = [];
        foreach ($request->file($field) as $file) {
            $paths[] = $file->store('craft-inspections', 'public');
        }

        return $paths;
    }

    private function mergePhotos($existing, Request $request, $field, $removeField)
    {
        $existing = $existing ?? [];
        $toRemove = $request->input($removeField, []);

        foreach ($toRemove as $path) {
            if (($key = array_search($path, $existing)) !== false) {
                Storage::disk('public')->delete($path);
                unset($existing[$key]);
            }
        }

        $existing = array_values($existing);
        $newPhotos = $this->storePhotos($request, $field);

        return array_merge($existing, $newPhotos);
    }
}
