<?php

namespace App\Http\Controllers;

use App\Models\CareData;
use App\Models\CraftInspection;
use App\Models\OnsiteHandover;
use App\Models\Order;
use App\Models\PerformanceTest;
use App\Models\ServeData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OnsiteHandoverController extends Controller
{
    const REPORT_INFO_FIELDS = [
        'service_date', 'work_start_time', 'work_completion_time',
        'technician_name', 'assistant_technician', 'service_location', 'service_type', 'status',
    ];

    const CUSTOMER_INFO_FIELDS = [
        'customer_present_during_assembly', 'authorised_representative', 'service_address',
    ];

    const BUILD_INFO_FIELDS = [
        'pc_purpose', 'operating_system', 'operating_system_version',
    ];

    const STUDIO_DOCS_FIELDS = [
        'component_serial_numbers_matched', 'customer_order_specification_verified',
        'required_components_present', 'required_tools_present', 'required_consumables_present',
        'studio_docs_notes',
    ];

    const ARRIVAL_FIELDS = [
        'arrival_time', 'service_environment',
        'workspace_available', 'adequate_lighting', 'stable_work_surface', 'sufficient_working_space',
        'power_outlet_available', 'internet_available', 'customer_present_at_arrival', 'assembly_area_approved_by_customer',
        'arrival_notes',
    ];

    const TRANSPORTATION_FIELDS = [
        'transport_case_note', 'transport_case_status',
        'component_packaging_note', 'component_packaging_status',
        'security_seal_intact', 'no_signs_of_transit_damage', 'accessories_present', 'documentation_present',
        'transportation_notes', 'transportation_verdict',
    ];

    const ASSEMBLY_FIELDS = [
        'cpu_installed', 'memory_installed', 'storage_installed', 'cpu_cooler_installed', 'motherboard_installed',
        'power_supply_installed', 'case_fans_installed', 'graphics_card_installed', 'cable_management_completed',
        'assembly_notes',
    ];

    const POST_BUILD_HARDWARE_FIELDS = [
        'system_powered_on', 'post_successful', 'bios_accessible', 'cpu_detected', 'memory_detected',
        'storage_detected', 'graphics_card_detected', 'cpu_cooler_operating', 'case_fans_operating', 'no_abnormal_noise',
        'post_build_hardware_notes',
    ];

    const POST_BUILD_SOFTWARE_FIELDS = [
        'windows_boot_successful', 'windows_activation_verified', 'display_output_verified', 'network_connected',
        'internet_accessible', 'audio_output_verified', 'usb_ports_verified', 'rgb_lighting_verified',
        'post_build_software_notes',
    ];

    public function show($orderId, $round = 1)
    {
        $order = Order::with(['customer', 'craft'])->find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            $handover = OnsiteHandover::create([
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
                'onsite_handover' => $handover,
                'studio_inspection_report_completed' => $craftInspection ? $craftInspection->status === 'completed' : false,
                'studio_inspection_report_id' => $craftInspection ? $craftInspection->id : null,
                'performance_testing_report_completed' => $performanceTest ? $performanceTest->status === 'completed' : false,
                'performance_testing_report_id' => $performanceTest ? $performanceTest->id : null,
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
        $nextId = OnsiteHandover::count() + 1;

        return 'OSH-QVCT-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    public function updateReportInfo(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'service_date' => 'nullable|date',
            'work_start_time' => 'nullable|string|max:255',
            'work_completion_time' => 'nullable|string|max:255',
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

    public function updateCustomerInfo(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_present_during_assembly' => 'nullable|in:yes,no,partially',
            'authorised_representative' => 'nullable|string|max:255',
            'service_address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::CUSTOMER_INFO_FIELDS));
            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Customer information updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update customer information', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateBuildInfo(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'pc_purpose' => 'nullable|string|max:255',
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
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'component_serial_numbers_matched' => 'nullable|boolean',
            'customer_order_specification_verified' => 'nullable|boolean',
            'required_components_present' => 'nullable|boolean',
            'required_tools_present' => 'nullable|boolean',
            'required_consumables_present' => 'nullable|boolean',
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

    public function updateArrival(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'arrival_time' => 'nullable|string|max:255',
            'service_environment' => 'nullable|in:residential,office,studio,commercial,other',
            'workspace_available' => 'nullable|boolean',
            'adequate_lighting' => 'nullable|boolean',
            'stable_work_surface' => 'nullable|boolean',
            'sufficient_working_space' => 'nullable|boolean',
            'power_outlet_available' => 'nullable|boolean',
            'internet_available' => 'nullable|boolean',
            'customer_present_at_arrival' => 'nullable|boolean',
            'assembly_area_approved_by_customer' => 'nullable|boolean',
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

    public function updateTransportation(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'transport_case_note' => 'nullable|string|max:255',
            'transport_case_status' => 'nullable|in:sound,damaged',
            'transport_case_photos.*' => 'nullable|image|max:5120',
            'remove_transport_case_photos' => 'nullable|array',
            'component_packaging_note' => 'nullable|string|max:255',
            'component_packaging_status' => 'nullable|in:sound,damaged',
            'component_packaging_photos.*' => 'nullable|image|max:5120',
            'remove_component_packaging_photos' => 'nullable|array',
            'security_seal_intact' => 'nullable|boolean',
            'no_signs_of_transit_damage' => 'nullable|boolean',
            'accessories_present' => 'nullable|boolean',
            'documentation_present' => 'nullable|boolean',
            'transportation_notes' => 'nullable|string|max:1000',
            'transportation_verdict' => 'nullable|in:sound_ready,issue_found',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::TRANSPORTATION_FIELDS));

            if ($request->hasFile('transport_case_photos') || $request->filled('remove_transport_case_photos')) {
                $handover->transport_case_photos = $this->mergePhotos($handover->transport_case_photos, $request, 'transport_case_photos', 'remove_transport_case_photos');
            }
            if ($request->hasFile('component_packaging_photos') || $request->filled('remove_component_packaging_photos')) {
                $handover->component_packaging_photos = $this->mergePhotos($handover->component_packaging_photos, $request, 'component_packaging_photos', 'remove_component_packaging_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Transportation inspection updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update transportation inspection', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateAssembly(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cpu_installed' => 'nullable|boolean',
            'memory_installed' => 'nullable|boolean',
            'storage_installed' => 'nullable|boolean',
            'cpu_cooler_installed' => 'nullable|boolean',
            'motherboard_installed' => 'nullable|boolean',
            'power_supply_installed' => 'nullable|boolean',
            'case_fans_installed' => 'nullable|boolean',
            'graphics_card_installed' => 'nullable|boolean',
            'cable_management_completed' => 'nullable|boolean',
            'assembly_notes' => 'nullable|string|max:1000',
            'assembly_photos.*' => 'nullable|image|max:5120',
            'remove_assembly_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::ASSEMBLY_FIELDS));

            if ($request->hasFile('assembly_photos') || $request->filled('remove_assembly_photos')) {
                $handover->assembly_photos = $this->mergePhotos($handover->assembly_photos, $request, 'assembly_photos', 'remove_assembly_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Assembly updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update assembly', 'error' => $e->getMessage()], 500);
        }
    }

    public function updatePostBuildHardware(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'system_powered_on' => 'nullable|boolean',
            'post_successful' => 'nullable|boolean',
            'bios_accessible' => 'nullable|boolean',
            'cpu_detected' => 'nullable|boolean',
            'memory_detected' => 'nullable|boolean',
            'storage_detected' => 'nullable|boolean',
            'graphics_card_detected' => 'nullable|boolean',
            'cpu_cooler_operating' => 'nullable|boolean',
            'case_fans_operating' => 'nullable|boolean',
            'no_abnormal_noise' => 'nullable|boolean',
            'post_build_hardware_notes' => 'nullable|string|max:1000',
            'post_build_hardware_photos.*' => 'nullable|image|max:5120',
            'remove_post_build_hardware_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::POST_BUILD_HARDWARE_FIELDS));

            if ($request->hasFile('post_build_hardware_photos') || $request->filled('remove_post_build_hardware_photos')) {
                $handover->post_build_hardware_photos = $this->mergePhotos($handover->post_build_hardware_photos, $request, 'post_build_hardware_photos', 'remove_post_build_hardware_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Post-build hardware verification updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update post-build hardware verification', 'error' => $e->getMessage()], 500);
        }
    }

    public function updatePostBuildSoftware(Request $request, $orderId, $round = 1)
    {
        $handover = OnsiteHandover::where('order_id', $orderId)->where('round', $round)->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Onsite handover not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'windows_boot_successful' => 'nullable|boolean',
            'windows_activation_verified' => 'nullable|boolean',
            'display_output_verified' => 'nullable|boolean',
            'network_connected' => 'nullable|boolean',
            'internet_accessible' => 'nullable|boolean',
            'audio_output_verified' => 'nullable|boolean',
            'usb_ports_verified' => 'nullable|boolean',
            'rgb_lighting_verified' => 'nullable|boolean',
            'post_build_software_notes' => 'nullable|string|max:1000',
            'post_build_software_photos.*' => 'nullable|image|max:5120',
            'remove_post_build_software_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $handover->fill($request->only(self::POST_BUILD_SOFTWARE_FIELDS));

            if ($request->hasFile('post_build_software_photos') || $request->filled('remove_post_build_software_photos')) {
                $handover->post_build_software_photos = $this->mergePhotos($handover->post_build_software_photos, $request, 'post_build_software_photos', 'remove_post_build_software_photos');
            }

            $handover->save();

            return response()->json([
                'success' => true,
                'message' => 'Post-build software verification updated successfully',
                'data' => $handover->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update post-build software verification', 'error' => $e->getMessage()], 500);
        }
    }

    private function storePhotos(Request $request, $field)
    {
        if (!$request->hasFile($field)) {
            return [];
        }

        $paths = [];
        foreach ($request->file($field) as $file) {
            $paths[] = $file->store('onsite-handovers', 'public');
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
