<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesPhotoEvidence;
use App\Models\Order;
use App\Models\PerformanceTest;
use App\Models\PerformanceTestChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PerformanceTestController extends Controller
{
    use ValidatesPhotoEvidence;

    // Fixed checklist item sets, seeded on first access -- these are not
    // user-added/removable like Craft Inspection's per-order-part items.
    const ASSEMBLY_ITEMS = [
        'cpu_installation' => 'CPU Installation',
        'memory_installation' => 'Memory Installation',
        'storage_installation' => 'Storage Installation',
        'thermal_paste_installation' => 'Thermal Paste Installation',
        'cooler_installation' => 'Air Cooler Installation',
        'motherboard_installation' => 'Motherboard Installation',
        'power_supply_installation' => 'Power Supply Installation',
        'fans_installation' => 'Fans Installation',
        'gpu_installation' => 'GPU Installation',
        'cable_management' => 'Cable Management',
        'cpu_power_connection_test' => 'CPU Power Connection Test',
        'front_panel_connection_test' => 'Front Panel Connection Test',
    ];

    const BOOT_VERIFICATION_ITEMS = [
        'initial_power_on' => 'Initial Power On',
        'post_successful' => 'POST Successful',
        'bios_accessible' => 'BIOS Accessible',
        'cpu_detected' => 'CPU Detected',
        'memory_detected' => 'Memory Detected',
        'storage_detected' => 'Storage Detected',
        'gpu_detected' => 'GPU Detected',
        'cpu_fan_detected' => 'CPU Fan Detected',
        'pump_detected' => 'Pump Detected',
        'case_fans_detected' => 'Case Fans Detected',
    ];

    const BIOS_CONFIGURATION_ITEMS = [
        'bios_updated' => 'BIOS Updated',
        'expo_xmp_enabled' => 'EXPO/XMP Enabled',
        'resizeable_bar_enabled' => 'Resizeable BAR Enabled',
        'tpm_enabled' => 'TPM Enabled',
        'secure_boot_enabled' => 'Secure Boot Enabled',
        'fan_curve_configured' => 'Fan Curve Configured',
        'boot_order_configured' => 'Boot Order Configured',
        'date_time_verified' => 'Date & Time Verified',
    ];

    const PARENT_FIELDS = [
        'cooling_solution', 'overall_cpu_performance', 'overall_gpu_performance', 'overall_system_stability',
        'overall_memory_validation', 'overall_storage_validation', 'overall_cpu_cooling_performance',
        'overall_cooling_system', 'overall_display_output', 'overall_network_wireless', 'overall_usb_ports',
        'overall_notes', 'thermal_paste_brand', 'thermal_paste_batch', 'thermal_paste_application_method',
        'os_installed', 'os_config_note', 'driver_chipset', 'driver_wifi', 'driver_gpu', 'driver_bluetooth',
        'driver_lan', 'driver_audio', 'drivers_note', 'applications_installed', 'applications_note',
    ];

    public function show($orderId, $round = 1)
    {
        $order = Order::with(['customer', 'craft'])->find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $performanceTest = PerformanceTest::firstOrCreate(
            ['order_id' => $orderId, 'round' => $round],
            ['status' => 'draft']
        );

        if ($performanceTest->checklistItems()->count() === 0) {
            $this->seedChecklistItems($performanceTest);
        }

        $performanceTest->load(['checklistItems' => function ($q) {
            $q->orderBy('id');
        }]);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'performance_test' => $performanceTest,
            ],
        ]);
    }

    public function update(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cooling_solution' => 'nullable|in:air_cooler,water_cooler',
            'overall_cpu_performance' => 'nullable|boolean',
            'overall_gpu_performance' => 'nullable|boolean',
            'overall_system_stability' => 'nullable|boolean',
            'overall_memory_validation' => 'nullable|boolean',
            'overall_storage_validation' => 'nullable|boolean',
            'overall_cpu_cooling_performance' => 'nullable|boolean',
            'overall_cooling_system' => 'nullable|boolean',
            'overall_display_output' => 'nullable|boolean',
            'overall_network_wireless' => 'nullable|boolean',
            'overall_usb_ports' => 'nullable|boolean',
            'overall_notes' => 'nullable|string',
            'thermal_paste_brand' => 'nullable|string|max:255',
            'thermal_paste_batch' => 'nullable|string|max:255',
            'thermal_paste_application_method' => 'nullable|string|max:255',
            'ready_for_first_boot' => 'boolean',
            'ready_for_bios_configuration' => 'boolean',
            'ready_for_stability_testing' => 'boolean',
            'ready_for_performance_testing' => 'boolean',
            'ready_for_stress_testing' => 'boolean',
            'os_installed' => 'nullable|string|max:255',
            'windows_activation' => 'boolean',
            'windows_update' => 'boolean',
            'os_config_note' => 'nullable|string|max:1000',
            'os_config_photos.*' => 'nullable|image|max:5120',
            'remove_os_config_photos' => 'nullable|array',
            'driver_chipset' => 'boolean',
            'driver_wifi' => 'boolean',
            'driver_gpu' => 'boolean',
            'driver_bluetooth' => 'boolean',
            'driver_lan' => 'boolean',
            'driver_audio' => 'boolean',
            'drivers_note' => 'nullable|string|max:1000',
            'drivers_photos.*' => 'nullable|image|max:5120',
            'remove_drivers_photos' => 'nullable|array',
            'applications_installed' => 'nullable|string',
            'applications_note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $previousCoolingSolution = $performanceTest->cooling_solution;

        $performanceTest->fill($request->only(self::PARENT_FIELDS));
        $performanceTest->ready_for_first_boot = $request->boolean('ready_for_first_boot');
        $performanceTest->ready_for_bios_configuration = $request->boolean('ready_for_bios_configuration');
        $performanceTest->ready_for_stability_testing = $request->boolean('ready_for_stability_testing');
        $performanceTest->ready_for_performance_testing = $request->boolean('ready_for_performance_testing');
        $performanceTest->ready_for_stress_testing = $request->boolean('ready_for_stress_testing');
        $performanceTest->windows_activation = $request->boolean('windows_activation');
        $performanceTest->windows_update = $request->boolean('windows_update');
        $performanceTest->driver_chipset = $request->boolean('driver_chipset');
        $performanceTest->driver_wifi = $request->boolean('driver_wifi');
        $performanceTest->driver_gpu = $request->boolean('driver_gpu');
        $performanceTest->driver_bluetooth = $request->boolean('driver_bluetooth');
        $performanceTest->driver_lan = $request->boolean('driver_lan');
        $performanceTest->driver_audio = $request->boolean('driver_audio');

        if ($request->hasFile('os_config_photos') || $request->filled('remove_os_config_photos')) {
            $performanceTest->os_config_photos = $this->mergePhotos($performanceTest->os_config_photos, $request, 'os_config_photos', 'remove_os_config_photos');
        }
        if ($request->hasFile('drivers_photos') || $request->filled('remove_drivers_photos')) {
            $performanceTest->drivers_photos = $this->mergePhotos($performanceTest->drivers_photos, $request, 'drivers_photos', 'remove_drivers_photos');
        }

        $performanceTest->save();

        if ($request->filled('cooling_solution') && $request->cooling_solution !== $previousCoolingSolution) {
            $label = $request->cooling_solution === 'water_cooler' ? 'Water Cooler Installation' : 'Air Cooler Installation';
            PerformanceTestChecklistItem::where('performance_test_id', $performanceTest->id)
                ->where('item_key', 'cooler_installation')
                ->update(['item_label' => $label]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Performance test updated successfully',
            'data' => $performanceTest->fresh(),
        ]);
    }

    public function updateItem(Request $request, $orderId, $round, $itemId)
    {
        $item = PerformanceTestChecklistItem::whereHas('performanceTest', function ($q) use ($orderId, $round) {
            $q->where('order_id', $orderId)->where('round', $round);
        })->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Checklist item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pass,fail',
            'note' => 'nullable|string|max:1000',
            'photos.*' => 'nullable|image|max:5120',
            'remove_photos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $newPhotos = $request->file('photos') ?? [];
        $newPhotoCount = is_array($newPhotos) ? count($newPhotos) : ($newPhotos ? 1 : 0);
        $removePhotos = $request->input('remove_photos', []);
        $existingCount = max(0, count($item->photos ?? []) - count($removePhotos));

        $groupErrors = $this->validatePhotoEvidence($request->status, 'pass', $request->note, $existingCount, $newPhotoCount);
        if ($groupErrors) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $groupErrors], 422);
        }

        $item->status = $request->status;
        $item->note = $request->note;
        $item->photos = $this->mergePhotos($item->photos, $request, 'photos', 'remove_photos');
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Checklist item updated successfully',
            'data' => $item->fresh(),
        ]);
    }

    public function complete($orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $performanceTest->status = 'completed';
        $performanceTest->save();

        return response()->json([
            'success' => true,
            'message' => 'Performance test marked as completed',
            'data' => $performanceTest,
        ]);
    }

    private function seedChecklistItems(PerformanceTest $performanceTest)
    {
        $sections = [
            'assembly' => self::ASSEMBLY_ITEMS,
            'boot_verification' => self::BOOT_VERIFICATION_ITEMS,
            'bios_configuration' => self::BIOS_CONFIGURATION_ITEMS,
        ];

        foreach ($sections as $section => $items) {
            foreach ($items as $key => $label) {
                if ($section === 'assembly' && $key === 'cooler_installation' && $performanceTest->cooling_solution === 'water_cooler') {
                    $label = 'Water Cooler Installation';
                }

                PerformanceTestChecklistItem::create([
                    'performance_test_id' => $performanceTest->id,
                    'section' => $section,
                    'item_key' => $key,
                    'item_label' => $label,
                    'status' => 'pass',
                    'note' => null,
                    'photos' => [],
                ]);
            }
        }
    }

    private function storePhotos(Request $request, $field)
    {
        if (!$request->hasFile($field)) {
            return [];
        }

        $paths = [];
        foreach ($request->file($field) as $file) {
            $paths[] = $file->store('performance-tests', 'public');
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
