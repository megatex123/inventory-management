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
}
