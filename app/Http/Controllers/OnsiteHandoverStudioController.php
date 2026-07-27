<?php

namespace App\Http\Controllers;

use App\Models\CareData;
use App\Models\CraftInspection;
use App\Models\OnsiteHandoverStudio;
use App\Models\Order;
use App\Models\PerformanceTest;
use App\Models\ServeData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OnsiteHandoverStudioController extends Controller
{
    const REPORT_INFO_FIELDS = [
        'report_version', 'service_date', 'handover_completion_time',
        'technician_name', 'assistant_technician', 'service_location', 'service_type', 'status',
    ];

    const BUILD_INFO_FIELDS = [
        'operating_system', 'operating_system_version',
    ];

    const STUDIO_DOCS_FIELDS = [
        'security_seal_verified_before_delivery', 'studio_docs_notes',
    ];

    const CUSTOMER_ACCEPTANCE_FIELDS = [
        'physical_condition_accepted', 'system_boot_verified', 'display_verified', 'accessories_received',
        'documentation_received', 'customer_demonstration_completed', 'customer_questions_addressed',
        'customer_acceptance_notes',
    ];

    const ARRIVAL_FIELDS = [
        'arrival_time', 'workspace_available', 'power_outlet_available', 'display_available',
        'keyboard_available', 'mouse_available', 'internet_available', 'arrival_notes',
    ];

    const POST_TRANSPORT_FIELDS = [
        'gpu_securely_installed', 'memory_fully_seated', 'cpu_cooler_secure', 'power_connections_secure',
        'storage_secure', 'no_loose_cables', 'no_loose_screws', 'post_transport_notes',
    ];

    const POST_HANDOVER_FIELDS = [
        'system_powered_on', 'post_successful', 'windows_boot_successful', 'display_output_verified',
        'network_connected', 'internet_accessible', 'audio_verified', 'usb_ports_verified',
        'rgb_lighting_verified', 'post_handover_notes',
    ];

    public function show($orderId, $round = 1)
    {
        $order = Order::with(['customer', 'craft'])->find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            $handover = OnsiteHandoverStudio::create([
                'order_id' => $orderId,
                'round' => $round,
                'report_id' => $this->generateReportId(),
                'status' => 'in_progress',
            ]);
        }

        $craftInspection = CraftInspection::where('order_id', $orderId)->orderByDesc('round')->first();
        $performanceTest = PerformanceTest::where('order_id', $orderId)->orderByDesc('round')->first();
        $serveData = ServeData::where('order_id', $orderId)->with('serve')->orderByDesc('id')->first();
        $careData = CareData::where('order_id', $orderId)->with('care')->orderByDesc('id')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'onsite_handover_studio' => $handover,
                'studio_inspection_report_completed' => $craftInspection ? $craftInspection->status === 'completed' : false,
                'studio_inspection_report_id' => $craftInspection ? $order->order_id : null,
                'performance_testing_report_completed' => $performanceTest ? $performanceTest->status === 'completed' : false,
                'performance_testing_report_id' => $performanceTest ? $order->order_id : null,
                'quivicraft_id' => $order->order_id,
                'quivicraft_plan' => optional($order->craft)->name,
                'quiviserve_id' => $serveData ? $serveData->serve_id : null,
                'quiviserve_customer_id' => optional($order->customer)->customer_id,
                'quiviserve_plan' => $serveData ? optional($serveData->serve)->name : null,
                'quivicare_id' => $careData ? $careData->care_id : null,
                'quivicare_plan' => $careData ? optional($careData->care)->name : null,
            ],
        ]);
    }

    private function generateReportId()
    {
        $nextId = OnsiteHandoverStudio::count() + 1;

        return 'OSH-STD-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    public function updateReportInfo(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'report_version' => 'nullable|string|max:255',
            'service_date' => 'nullable|date',
            'handover_completion_time' => 'nullable|string|max:255',
            'technician_name' => 'nullable|string|max:255',
            'assistant_technician' => 'nullable|string|max:255',
            'service_location' => 'nullable|string|max:255',
            'service_type' => 'nullable|in:full_onsite_assembly,full_onsite_assembly_tag_along,studio_assembly',
            'status' => 'nullable|in:in_progress,completed,deferred,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::REPORT_INFO_FIELDS));
            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Report information updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update report information', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateBuildInfo(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'operating_system' => 'nullable|string|max:255',
            'operating_system_version' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::BUILD_INFO_FIELDS));
            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Build information updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update build information', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateStudioDocs(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'security_seal_verified_before_delivery' => 'nullable|boolean',
            'studio_docs_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::STUDIO_DOCS_FIELDS));
            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Studio documentation verification updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update studio documentation verification', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateCustomerAcceptance(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'physical_condition_accepted' => 'nullable|boolean',
            'system_boot_verified' => 'nullable|boolean',
            'display_verified' => 'nullable|boolean',
            'accessories_received' => 'nullable|boolean',
            'documentation_received' => 'nullable|boolean',
            'customer_demonstration_completed' => 'nullable|boolean',
            'customer_questions_addressed' => 'nullable|boolean',
            'customer_acceptance_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::CUSTOMER_ACCEPTANCE_FIELDS));
            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Customer acceptance updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update customer acceptance', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateArrival(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'arrival_time' => 'nullable|string|max:255',
            'workspace_available' => 'nullable|boolean',
            'power_outlet_available' => 'nullable|boolean',
            'display_available' => 'nullable|boolean',
            'keyboard_available' => 'nullable|boolean',
            'mouse_available' => 'nullable|boolean',
            'internet_available' => 'nullable|boolean',
            'arrival_notes' => 'nullable|string|max:1000',
            'arrival_photos.*' => 'nullable|image|max:5120',
            'remove_arrival_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::ARRIVAL_FIELDS));

            if ($request->hasFile('arrival_photos') || $request->filled('remove_arrival_photos')) {
                $handover->arrival_photos = $this->mergePhotos($handover->arrival_photos, $request, 'arrival_photos', 'remove_arrival_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Arrival verification updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update arrival verification', 'error' => $e->getMessage()], 500);
        }
    }

    public function updatePostTransport(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'gpu_securely_installed' => 'nullable|boolean',
            'memory_fully_seated' => 'nullable|boolean',
            'cpu_cooler_secure' => 'nullable|boolean',
            'power_connections_secure' => 'nullable|boolean',
            'storage_secure' => 'nullable|boolean',
            'no_loose_cables' => 'nullable|boolean',
            'no_loose_screws' => 'nullable|boolean',
            'post_transport_notes' => 'nullable|string|max:1000',
            'post_transport_photos.*' => 'nullable|image|max:5120',
            'remove_post_transport_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::POST_TRANSPORT_FIELDS));

            if ($request->hasFile('post_transport_photos') || $request->filled('remove_post_transport_photos')) {
                $handover->post_transport_photos = $this->mergePhotos($handover->post_transport_photos, $request, 'post_transport_photos', 'remove_post_transport_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Post-transport hardware verification updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update post-transport hardware verification', 'error' => $e->getMessage()], 500);
        }
    }

    public function updatePostHandover(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandoverStudio::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'system_powered_on' => 'nullable|boolean',
            'post_successful' => 'nullable|boolean',
            'windows_boot_successful' => 'nullable|boolean',
            'display_output_verified' => 'nullable|boolean',
            'network_connected' => 'nullable|boolean',
            'internet_accessible' => 'nullable|boolean',
            'audio_verified' => 'nullable|boolean',
            'usb_ports_verified' => 'nullable|boolean',
            'rgb_lighting_verified' => 'nullable|boolean',
            'post_handover_notes' => 'nullable|string|max:1000',
            'post_handover_photos.*' => 'nullable|image|max:5120',
            'remove_post_handover_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::POST_HANDOVER_FIELDS));

            if ($request->hasFile('post_handover_photos') || $request->filled('remove_post_handover_photos')) {
                $handover->post_handover_photos = $this->mergePhotos($handover->post_handover_photos, $request, 'post_handover_photos', 'remove_post_handover_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Post-handover system verification updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update post-handover system verification', 'error' => $e->getMessage()], 500);
        }
    }

    private function storePhotos(Request $request, $field)
    {
        if (!$request->hasFile($field)) {
            return [];
        }

        $paths = [];
        foreach ($request->file($field) as $file) {
            $paths[] = $file->store('onsite-handovers-studio', 'public');
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
