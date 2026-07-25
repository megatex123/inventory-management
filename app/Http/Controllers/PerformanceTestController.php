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
        'cooling_solution',
        'overall_display_output', 'overall_network_wireless', 'overall_usb_ports',
        'overall_notes', 'thermal_paste_brand', 'thermal_paste_batch', 'thermal_paste_application_method',
        'os_installed', 'os_config_note', 'drivers_note', 'applications_installed', 'applications_note',
    ];

    const CPU_RESULT_FIELDS = [
        'duration', 'threads_mode',
        'avg_temp_c', 'max_temp_c', 'avg_clock_mhz', 'peak_package_power_w',
        'thermal_throttling', 'whea_errors', 'system_crash',
        'no_thermal_throttling', 'no_whea_errors', 'no_application_crash', 'stable_clock_speed', 'temperature_within_range',
        'single_core_score', 'multi_core_score', 'benchmark_temp_c', 'benchmark_peak_power_w',
        'benchmark_completed', 'performance_within_range', 'no_thermal_throttling_benchmark',
        'idle_temp_c', 'load_temp_c', 'ccd_temp_c', 'core_voltage_v', 'avg_effective_clock_mhz', 'peak_package_power_benchmark_w',
        'stability_test_passed', 'benchmark_test_passed', 'thermal_performance_passed', 'clock_stability_passed', 'power_delivery_passed',
        'overall_cpu_validation', 'technician_notes',
    ];

    const GPU_RESULT_FIELDS = [
        'duration', 'vram_test',
        'avg_temp_c', 'max_temp_c', 'max_hotspot_temp_c', 'avg_clock_mhz', 'peak_power_draw_w',
        'thermal_throttling', 'visual_artifacts', 'driver_crash',
        'no_visual_artifacts', 'no_driver_crash', 'stable_clock_speed', 'temperature_within_range',
        'gpu_score', 'overall_score', 'benchmark_temp_c', 'benchmark_peak_power_w',
        'benchmark_completed', 'performance_within_range', 'no_performance_anomalies',
        'idle_temp_c', 'load_temp_c', 'hotspot_temp_c', 'core_clock_mhz', 'memory_clock_mhz', 'power_draw_w', 'fan_speed_rpm',
        'stability_test_passed', 'benchmark_test_passed', 'thermal_performance_passed', 'clock_stability_passed', 'cooling_performance_passed',
        'overall_gpu_validation', 'technician_notes',
    ];

    const SYSTEM_STABILITY_RESULT_FIELDS = [
        'duration', 'ambient_temp_c', 'windows_power_plan',
        'max_cpu_temp_c', 'max_gpu_temp_c', 'cpu_package_power_w', 'gpu_power_draw_w', 'total_system_power_w',
        'cpu_clock_stability', 'gpu_clock_stability',
        'unexpected_shutdown', 'bsod', 'application_crash', 'whea_errors', 'thermal_throttling',
        'test_completed_successfully', 'no_shutdowns', 'no_bsod', 'no_whea_errors', 'no_thermal_throttling', 'stable_cpu_gpu_operation',
        'cpu_temp_c', 'gpu_temp_c', 'motherboard_temp_c', 'vrm_temp_c', 'chipset_temp_c', 'cpu_fan_speed_rpm', 'pump_speed_rpm',
        'combined_load_stability', 'thermal_performance', 'power_delivery', 'cooling_performance',
        'overall_system_stability', 'technician_notes',
    ];

    const MEMORY_RESULT_FIELDS = [
        'duration', 'memory_capacity', 'memory_configuration', 'expo_xmp_profile', 'memory_frequency_mts',
        'memory_timings', 'memory_passes',
        'total_passes_completed', 'total_tests_completed', 'memory_errors_detected',
        'test_completed_successfully', 'zero_memory_errors', 'stable_expo_xmp_operation',
        'capacity_expected', 'capacity_detected', 'capacity_status',
        'configuration_expected', 'configuration_detected', 'configuration_status',
        'frequency_expected', 'frequency_detected', 'frequency_status',
        'expo_xmp_expected', 'expo_xmp_detected', 'expo_xmp_status',
        'memory_stability_test', 'memory_frequency_verified', 'error_detection',
        'overall_memory_validation', 'technician_notes',
    ];

    const STORAGE_RESULT_FIELDS = [
        'duration', 'storage_device', 'interface', 'capacity', 'firmware_version',
        'health_status', 'drive_temp_c', 'power_on_hours', 'interface_mode',
        'health_status_good', 'drive_detected_correctly', 'firmware_verified', 'temperature_within_range',
        'sequential_read_speed_mbs', 'sequential_write_speed_mbs',
        'benchmark_completed', 'read_performance_within_range', 'write_performance_within_range',
        'driver_expected', 'driver_detected', 'driver_status',
        'storage_health_verification', 'firmware_verification', 'performance_verification', 'temperature_verification',
        'overall_storage_validation', 'technician_notes',
    ];

    const COOLING_PERFORMANCE_RESULT_FIELDS = [
        'cooling_solution', 'duration', 'ambient_temp_c',
        'cpu_idle_temp_c', 'cpu_load_temp_c', 'gpu_idle_temp_c', 'gpu_load_temp_c',
        'vrm_idle_temp_c', 'vrm_load_temp_c', 'chipset_idle_temp_c', 'chipset_load_temp_c',
        'cpu_temp_within_range', 'gpu_temp_within_range', 'vrm_temp_within_range', 'chipset_temp_within_range',
        'cooling_operating_normally', 'no_thermal_throttling', 'temps_stable_under_load',
        'cpu_cooling_performance', 'gpu_cooling_performance', 'motherboard_cooling_performance',
        'overall_cpu_cooling_performance', 'technician_notes',
    ];

    const COOLING_SYSTEM_RESULT_FIELDS = [
        'cooling_solution', 'fan_control_mode', 'fan_curve',
        'cpu_fan_rpm', 'cpu_pump_rpm', 'front_fans_rpm', 'rear_fans_rpm', 'top_fans_rpm', 'bottom_fans_rpm',
        'cpu_fan_detected', 'cpu_pump_detected', 'all_case_fans_detected',
        'cpu_fan_rpm_stable', 'cpu_pump_rpm_stable', 'front_fan_rpm_stable', 'rear_fan_rpm_stable',
        'top_fan_rpm_stable', 'bottom_fan_rpm_stable',
        'all_devices_operational', 'no_fan_failures', 'stable_rpm_monitoring',
        'front_fan_direction', 'rear_fan_direction', 'top_fan_direction', 'bottom_fan_direction',
        'cpu_cooler_operation', 'pump_operation', 'chassis_fan_cooling_operation',
        'overall_cooling_system', 'technician_notes',
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

        $performanceTest->cpuResults()->firstOrCreate([]);
        $performanceTest->gpuResults()->firstOrCreate([]);
        $performanceTest->systemStabilityResults()->firstOrCreate([]);
        $performanceTest->memoryResults()->firstOrCreate([]);
        $performanceTest->storageResults()->firstOrCreate([]);
        $performanceTest->coolingPerformanceResults()->firstOrCreate([]);
        $performanceTest->coolingSystemResults()->firstOrCreate([]);

        $performanceTest->load([
            'checklistItems' => function ($q) {
                $q->orderBy('id');
            },
            'cpuResults',
            'gpuResults',
            'systemStabilityResults',
            'memoryResults',
            'storageResults',
            'coolingPerformanceResults',
            'coolingSystemResults',
        ]);

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

        try {
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
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update performance test', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateCpuResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|string|max:255',
            'threads_mode' => 'nullable|in:auto,all',
            'avg_temp_c' => 'nullable|numeric',
            'max_temp_c' => 'nullable|numeric',
            'avg_clock_mhz' => 'nullable|integer',
            'peak_package_power_w' => 'nullable|numeric',
            'thermal_throttling' => 'nullable|boolean',
            'whea_errors' => 'nullable|boolean',
            'system_crash' => 'nullable|boolean',
            'no_thermal_throttling' => 'nullable|boolean',
            'no_whea_errors' => 'nullable|boolean',
            'no_application_crash' => 'nullable|boolean',
            'stable_clock_speed' => 'nullable|boolean',
            'temperature_within_range' => 'nullable|boolean',
            'single_core_score' => 'nullable|integer',
            'multi_core_score' => 'nullable|integer',
            'benchmark_temp_c' => 'nullable|numeric',
            'benchmark_peak_power_w' => 'nullable|numeric',
            'benchmark_completed' => 'nullable|boolean',
            'performance_within_range' => 'nullable|boolean',
            'no_thermal_throttling_benchmark' => 'nullable|boolean',
            'idle_temp_c' => 'nullable|numeric',
            'load_temp_c' => 'nullable|numeric',
            'ccd_temp_c' => 'nullable|numeric',
            'core_voltage_v' => 'nullable|numeric',
            'avg_effective_clock_mhz' => 'nullable|integer',
            'peak_package_power_benchmark_w' => 'nullable|numeric',
            'stability_test_passed' => 'nullable|boolean',
            'benchmark_test_passed' => 'nullable|boolean',
            'thermal_performance_passed' => 'nullable|boolean',
            'clock_stability_passed' => 'nullable|boolean',
            'power_delivery_passed' => 'nullable|boolean',
            'overall_cpu_validation' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $cpuResults = $performanceTest->cpuResults()->firstOrCreate([]);
            $cpuResults->fill($request->only(self::CPU_RESULT_FIELDS));
            $cpuResults->save();

            if ($request->has('overall_cpu_validation')) {
                $performanceTest->overall_cpu_performance = $request->boolean('overall_cpu_validation');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'CPU results updated successfully',
                'data' => $cpuResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update CPU results', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateGpuResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|string|max:255',
            'vram_test' => 'nullable|boolean',
            'avg_temp_c' => 'nullable|numeric',
            'max_temp_c' => 'nullable|numeric',
            'max_hotspot_temp_c' => 'nullable|numeric',
            'avg_clock_mhz' => 'nullable|integer',
            'peak_power_draw_w' => 'nullable|numeric',
            'thermal_throttling' => 'nullable|boolean',
            'visual_artifacts' => 'nullable|boolean',
            'driver_crash' => 'nullable|boolean',
            'no_visual_artifacts' => 'nullable|boolean',
            'no_driver_crash' => 'nullable|boolean',
            'stable_clock_speed' => 'nullable|boolean',
            'temperature_within_range' => 'nullable|boolean',
            'gpu_score' => 'nullable|integer',
            'overall_score' => 'nullable|integer',
            'benchmark_temp_c' => 'nullable|numeric',
            'benchmark_peak_power_w' => 'nullable|numeric',
            'benchmark_completed' => 'nullable|boolean',
            'performance_within_range' => 'nullable|boolean',
            'no_performance_anomalies' => 'nullable|boolean',
            'idle_temp_c' => 'nullable|numeric',
            'load_temp_c' => 'nullable|numeric',
            'hotspot_temp_c' => 'nullable|numeric',
            'core_clock_mhz' => 'nullable|integer',
            'memory_clock_mhz' => 'nullable|integer',
            'power_draw_w' => 'nullable|numeric',
            'fan_speed_rpm' => 'nullable|integer',
            'stability_test_passed' => 'nullable|boolean',
            'benchmark_test_passed' => 'nullable|boolean',
            'thermal_performance_passed' => 'nullable|boolean',
            'clock_stability_passed' => 'nullable|boolean',
            'cooling_performance_passed' => 'nullable|boolean',
            'overall_gpu_validation' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $gpuResults = $performanceTest->gpuResults()->firstOrCreate([]);
            $gpuResults->fill($request->only(self::GPU_RESULT_FIELDS));
            $gpuResults->save();

            if ($request->has('overall_gpu_validation')) {
                $performanceTest->overall_gpu_performance = $request->boolean('overall_gpu_validation');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'GPU results updated successfully',
                'data' => $gpuResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update GPU results', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateSystemStabilityResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|string|max:255',
            'ambient_temp_c' => 'nullable|numeric',
            'windows_power_plan' => 'nullable|in:high_performance,balanced',
            'max_cpu_temp_c' => 'nullable|numeric',
            'max_gpu_temp_c' => 'nullable|numeric',
            'cpu_package_power_w' => 'nullable|numeric',
            'gpu_power_draw_w' => 'nullable|numeric',
            'total_system_power_w' => 'nullable|numeric',
            'cpu_clock_stability' => 'nullable|in:stable,unstable',
            'gpu_clock_stability' => 'nullable|in:stable,unstable',
            'unexpected_shutdown' => 'nullable|boolean',
            'bsod' => 'nullable|boolean',
            'application_crash' => 'nullable|boolean',
            'whea_errors' => 'nullable|boolean',
            'thermal_throttling' => 'nullable|boolean',
            'test_completed_successfully' => 'nullable|boolean',
            'no_shutdowns' => 'nullable|boolean',
            'no_bsod' => 'nullable|boolean',
            'no_whea_errors' => 'nullable|boolean',
            'no_thermal_throttling' => 'nullable|boolean',
            'stable_cpu_gpu_operation' => 'nullable|boolean',
            'cpu_temp_c' => 'nullable|numeric',
            'gpu_temp_c' => 'nullable|numeric',
            'motherboard_temp_c' => 'nullable|numeric',
            'vrm_temp_c' => 'nullable|numeric',
            'chipset_temp_c' => 'nullable|numeric',
            'cpu_fan_speed_rpm' => 'nullable|integer',
            'pump_speed_rpm' => 'nullable|integer',
            'combined_load_stability' => 'nullable|boolean',
            'thermal_performance' => 'nullable|boolean',
            'power_delivery' => 'nullable|boolean',
            'cooling_performance' => 'nullable|boolean',
            'overall_system_stability' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $systemStabilityResults = $performanceTest->systemStabilityResults()->firstOrCreate([]);
            $systemStabilityResults->fill($request->only(self::SYSTEM_STABILITY_RESULT_FIELDS));
            $systemStabilityResults->save();

            if ($request->has('overall_system_stability')) {
                $performanceTest->overall_system_stability = $request->boolean('overall_system_stability');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'System stability results updated successfully',
                'data' => $systemStabilityResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update system stability results', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateMemoryResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|string|max:255',
            'memory_capacity' => 'nullable|string|max:255',
            'memory_configuration' => 'nullable|string|max:255',
            'expo_xmp_profile' => 'nullable|string|max:255',
            'memory_frequency_mts' => 'nullable|integer',
            'memory_timings' => 'nullable|string|max:255',
            'memory_passes' => 'nullable|string|max:255',
            'total_passes_completed' => 'nullable|integer',
            'total_tests_completed' => 'nullable|integer',
            'memory_errors_detected' => 'nullable|integer',
            'test_completed_successfully' => 'nullable|boolean',
            'zero_memory_errors' => 'nullable|boolean',
            'stable_expo_xmp_operation' => 'nullable|boolean',
            'capacity_expected' => 'nullable|string|max:255',
            'capacity_detected' => 'nullable|string|max:255',
            'capacity_status' => 'nullable|boolean',
            'configuration_expected' => 'nullable|string|max:255',
            'configuration_detected' => 'nullable|string|max:255',
            'configuration_status' => 'nullable|boolean',
            'frequency_expected' => 'nullable|string|max:255',
            'frequency_detected' => 'nullable|string|max:255',
            'frequency_status' => 'nullable|boolean',
            'expo_xmp_expected' => 'nullable|string|max:255',
            'expo_xmp_detected' => 'nullable|string|max:255',
            'expo_xmp_status' => 'nullable|boolean',
            'memory_stability_test' => 'nullable|boolean',
            'memory_frequency_verified' => 'nullable|boolean',
            'error_detection' => 'nullable|boolean',
            'overall_memory_validation' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $memoryResults = $performanceTest->memoryResults()->firstOrCreate([]);
            $memoryResults->fill($request->only(self::MEMORY_RESULT_FIELDS));
            $memoryResults->save();

            if ($request->has('overall_memory_validation')) {
                $performanceTest->overall_memory_validation = $request->boolean('overall_memory_validation');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Memory results updated successfully',
                'data' => $memoryResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update memory results', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateStorageResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|string|max:255',
            'storage_device' => 'nullable|string|max:255',
            'interface' => 'nullable|in:pcie_gen4,pcie_gen5,sata,hdd',
            'capacity' => 'nullable|string|max:255',
            'firmware_version' => 'nullable|string|max:255',
            'health_status' => 'nullable|in:good,warning,critical',
            'drive_temp_c' => 'nullable|numeric',
            'power_on_hours' => 'nullable|string|max:255',
            'interface_mode' => 'nullable|string|max:255',
            'health_status_good' => 'nullable|boolean',
            'drive_detected_correctly' => 'nullable|boolean',
            'firmware_verified' => 'nullable|boolean',
            'temperature_within_range' => 'nullable|boolean',
            'sequential_read_speed_mbs' => 'nullable|numeric',
            'sequential_write_speed_mbs' => 'nullable|numeric',
            'benchmark_completed' => 'nullable|boolean',
            'read_performance_within_range' => 'nullable|boolean',
            'write_performance_within_range' => 'nullable|boolean',
            'driver_expected' => 'nullable|string|max:255',
            'driver_detected' => 'nullable|string|max:255',
            'driver_status' => 'nullable|boolean',
            'storage_health_verification' => 'nullable|boolean',
            'firmware_verification' => 'nullable|boolean',
            'performance_verification' => 'nullable|boolean',
            'temperature_verification' => 'nullable|boolean',
            'overall_storage_validation' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $storageResults = $performanceTest->storageResults()->firstOrCreate([]);
            $storageResults->fill($request->only(self::STORAGE_RESULT_FIELDS));
            $storageResults->save();

            if ($request->has('overall_storage_validation')) {
                $performanceTest->overall_storage_validation = $request->boolean('overall_storage_validation');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Storage results updated successfully',
                'data' => $storageResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update storage results', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateCoolingPerformanceResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cooling_solution' => 'nullable|in:air_cooler,water_cooler',
            'duration' => 'nullable|string|max:255',
            'ambient_temp_c' => 'nullable|numeric',
            'cpu_idle_temp_c' => 'nullable|numeric',
            'cpu_load_temp_c' => 'nullable|numeric',
            'gpu_idle_temp_c' => 'nullable|numeric',
            'gpu_load_temp_c' => 'nullable|numeric',
            'vrm_idle_temp_c' => 'nullable|numeric',
            'vrm_load_temp_c' => 'nullable|numeric',
            'chipset_idle_temp_c' => 'nullable|numeric',
            'chipset_load_temp_c' => 'nullable|numeric',
            'cpu_temp_within_range' => 'nullable|boolean',
            'gpu_temp_within_range' => 'nullable|boolean',
            'vrm_temp_within_range' => 'nullable|boolean',
            'chipset_temp_within_range' => 'nullable|boolean',
            'cooling_operating_normally' => 'nullable|boolean',
            'no_thermal_throttling' => 'nullable|boolean',
            'temps_stable_under_load' => 'nullable|boolean',
            'cpu_cooling_performance' => 'nullable|boolean',
            'gpu_cooling_performance' => 'nullable|boolean',
            'motherboard_cooling_performance' => 'nullable|boolean',
            'overall_cpu_cooling_performance' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $coolingPerformanceResults = $performanceTest->coolingPerformanceResults()->firstOrCreate([]);
            $coolingPerformanceResults->fill($request->only(self::COOLING_PERFORMANCE_RESULT_FIELDS));
            $coolingPerformanceResults->save();

            if ($request->has('overall_cpu_cooling_performance')) {
                $performanceTest->overall_cpu_cooling_performance = $request->boolean('overall_cpu_cooling_performance');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Cooling performance results updated successfully',
                'data' => $coolingPerformanceResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update cooling performance results', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateCoolingSystemResults(Request $request, $orderId, $round = 1)
    {
        $performanceTest = PerformanceTest::where('order_id', $orderId)->where('round', $round)->first();

        if (!$performanceTest) {
            return response()->json(['success' => false, 'message' => 'Performance test not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cooling_solution' => 'nullable|in:air_cooler,water_cooler',
            'fan_control_mode' => 'nullable|in:pwm,dc',
            'fan_curve' => 'nullable|in:default,custom',
            'cpu_fan_rpm' => 'nullable|integer',
            'cpu_pump_rpm' => 'nullable|integer',
            'front_fans_rpm' => 'nullable|integer',
            'rear_fans_rpm' => 'nullable|integer',
            'top_fans_rpm' => 'nullable|integer',
            'bottom_fans_rpm' => 'nullable|integer',
            'cpu_fan_detected' => 'nullable|boolean',
            'cpu_pump_detected' => 'nullable|boolean',
            'all_case_fans_detected' => 'nullable|boolean',
            'cpu_fan_rpm_stable' => 'nullable|boolean',
            'cpu_pump_rpm_stable' => 'nullable|boolean',
            'front_fan_rpm_stable' => 'nullable|boolean',
            'rear_fan_rpm_stable' => 'nullable|boolean',
            'top_fan_rpm_stable' => 'nullable|boolean',
            'bottom_fan_rpm_stable' => 'nullable|boolean',
            'all_devices_operational' => 'nullable|boolean',
            'no_fan_failures' => 'nullable|boolean',
            'stable_rpm_monitoring' => 'nullable|boolean',
            'front_fan_direction' => 'nullable|string|max:255',
            'rear_fan_direction' => 'nullable|string|max:255',
            'top_fan_direction' => 'nullable|string|max:255',
            'bottom_fan_direction' => 'nullable|string|max:255',
            'cpu_cooler_operation' => 'nullable|boolean',
            'pump_operation' => 'nullable|boolean',
            'chassis_fan_cooling_operation' => 'nullable|boolean',
            'overall_cooling_system' => 'nullable|boolean',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $coolingSystemResults = $performanceTest->coolingSystemResults()->firstOrCreate([]);
            $coolingSystemResults->fill($request->only(self::COOLING_SYSTEM_RESULT_FIELDS));
            $coolingSystemResults->save();

            if ($request->has('overall_cooling_system')) {
                $performanceTest->overall_cooling_system = $request->boolean('overall_cooling_system');
                $performanceTest->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Cooling system results updated successfully',
                'data' => $coolingSystemResults->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update cooling system results', 'error' => $e->getMessage()], 500);
        }
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

        try {
            $item->status = $request->status;
            $item->note = $request->note;
            $item->photos = $this->mergePhotos($item->photos, $request, 'photos', 'remove_photos');
            $item->save();

            return response()->json([
                'success' => true,
                'message' => 'Checklist item updated successfully',
                'data' => $item->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update checklist item', 'error' => $e->getMessage()], 500);
        }
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
