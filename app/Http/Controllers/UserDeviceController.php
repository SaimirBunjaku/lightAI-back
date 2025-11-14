<?php

namespace App\Http\Controllers;

use App\Models\DeviceAnalysis;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserDeviceController extends Controller
{
    /**
     * Save a device to user's device list
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'device_analysis_id' => 'sometimes|exists:device_analyses,id',
                'device_name' => 'required|string|max:255',
                'device_category' => 'required|string|max:100',
                'device_brand' => 'nullable|string|max:100',
                'device_model' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:100',
                'typical_wattage' => 'nullable|string|max:50',
                'daily_kwh' => 'nullable|string|max:50',
                'annual_kwh' => 'nullable|string|max:50',
                'estimated_annual_cost' => 'nullable|string|max:50',
                'energy_saving_tips' => 'nullable|array',
            ]);

            $device = UserDevice::create([
                'user_id' => $request->user()->id,
                'device_analysis_id' => $validated['device_analysis_id'] ?? null,
                'device_name' => $validated['device_name'],
                'device_category' => $validated['device_category'],
                'device_brand' => $validated['device_brand'] ?? null,
                'device_model' => $validated['device_model'] ?? null,
                'location' => $validated['location'] ?? null,
                'typical_wattage' => $validated['typical_wattage'] ?? null,
                'daily_kwh' => $validated['daily_kwh'] ?? null,
                'annual_kwh' => $validated['annual_kwh'] ?? null,
                'estimated_annual_cost' => $validated['estimated_annual_cost'] ?? null,
                'energy_saving_tips' => $validated['energy_saving_tips'] ?? null,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device saved successfully',
                'data' => [
                    'device' => [
                        'id' => $device->id,
                        'name' => $device->device_name,
                        'category' => $device->device_category,
                        'brand' => $device->device_brand,
                        'model' => $device->device_model,
                        'location' => $device->location,
                        'energy' => [
                            'typical_wattage' => $device->typical_wattage,
                            'daily_kwh' => $device->daily_kwh,
                            'annual_kwh' => $device->annual_kwh,
                            'estimated_annual_cost' => $device->estimated_annual_cost,
                        ],
                        'is_active' => $device->is_active,
                        'added_at' => $device->created_at->toIso8601String(),
                    ],
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save device',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get all user's saved devices
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $devices = UserDevice::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->id,
                    'name' => $device->device_name,
                    'category' => $device->device_category,
                    'brand' => $device->device_brand,
                    'model' => $device->device_model,
                    'location' => $device->location,
                    'energy' => [
                        'typical_wattage' => $device->typical_wattage,
                        'daily_kwh' => $device->daily_kwh,
                        'annual_kwh' => $device->annual_kwh,
                        'estimated_annual_cost' => $device->estimated_annual_cost,
                    ],
                    'tips' => $device->energy_saving_tips,
                    'is_active' => $device->is_active,
                    'added_at' => $device->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $devices->count(),
                'devices' => $devices,
            ],
        ], 200);
    }

    /**
     * Get specific device details
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $device = UserDevice::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'device' => [
                    'id' => $device->id,
                    'name' => $device->device_name,
                    'category' => $device->device_category,
                    'brand' => $device->device_brand,
                    'model' => $device->device_model,
                    'location' => $device->location,
                    'energy' => [
                        'typical_wattage' => $device->typical_wattage,
                        'daily_kwh' => $device->daily_kwh,
                        'annual_kwh' => $device->annual_kwh,
                        'estimated_annual_cost' => $device->estimated_annual_cost,
                    ],
                    'tips' => $device->energy_saving_tips,
                    'is_active' => $device->is_active,
                    'added_at' => $device->created_at->toIso8601String(),
                ],
            ],
        ], 200);
    }

    /**
     * Update device information
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $device = UserDevice::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                ], 404);
            }

            $validated = $request->validate([
                'device_name' => 'sometimes|string|max:255',
                'location' => 'sometimes|nullable|string|max:100',
                'is_active' => 'sometimes|boolean',
            ]);

            $device->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Device updated successfully',
                'data' => [
                    'device' => [
                        'id' => $device->id,
                        'name' => $device->device_name,
                        'location' => $device->location,
                        'is_active' => $device->is_active,
                    ],
                ],
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update device',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Delete a device (soft delete by marking inactive)
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $device = UserDevice::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ], 404);
        }

        // Soft delete by marking as inactive
        $device->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Device removed successfully',
        ], 200);
    }

    /**
     * Get energy insights and statistics for user's devices
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function insights(Request $request): JsonResponse
    {
        $user = $request->user();
        $devices = UserDevice::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($devices->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_devices' => 0,
                    'message' => 'No devices saved yet. Start scanning devices to see insights!',
                ],
            ], 200);
        }

        // Calculate statistics
        $totalDevices = $devices->count();
        $devicesByCategory = $devices->groupBy('device_category')->map->count();
        $devicesByLocation = $devices->groupBy('location')->map->count();

        // Calculate energy consumption
        $totalDailyKwh = 0;
        $totalAnnualKwh = 0;
        $totalAnnualCost = 0;

        foreach ($devices as $device) {
            // Extract numeric values from ranges (e.g., "1.2 - 2.0" -> average)
            if ($device->daily_kwh) {
                $totalDailyKwh += $this->extractAverageValue($device->daily_kwh);
            }
            if ($device->annual_kwh) {
                $totalAnnualKwh += $this->extractAverageValue($device->annual_kwh);
            }
            if ($device->estimated_annual_cost) {
                $totalAnnualCost += $this->extractAverageValue($device->estimated_annual_cost);
            }
        }

        // Find highest consumers
        $highestConsumers = $devices->sortByDesc(function ($device) {
            return $this->extractAverageValue($device->annual_kwh ?? '0');
        })->take(3)->map(function ($device) {
            return [
                'name' => $device->device_name,
                'category' => $device->device_category,
                'annual_kwh' => $device->annual_kwh,
                'estimated_annual_cost' => $device->estimated_annual_cost,
            ];
        })->values();

        // Generate recommendations
        $recommendations = $this->generateRecommendations($devices, $totalAnnualCost);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_devices' => $totalDevices,
                    'estimated_daily_consumption' => round($totalDailyKwh, 2) . ' kWh',
                    'estimated_annual_consumption' => round($totalAnnualKwh, 2) . ' kWh',
                    'estimated_annual_cost' => '$' . round($totalAnnualCost, 2),
                    'average_cost_per_device' => '$' . round($totalAnnualCost / $totalDevices, 2),
                ],
                'breakdown' => [
                    'by_category' => $devicesByCategory,
                    'by_location' => $devicesByLocation,
                ],
                'highest_consumers' => $highestConsumers,
                'household_info' => [
                    'property_type' => $user->house_type,
                    'occupants' => $user->number_of_occupants,
                    'bedrooms' => $user->number_of_bedrooms,
                    'heating_type' => $user->heating_type,
                    'property_age' => $user->property_age,
                ],
                'recommendations' => $recommendations,
                'potential_savings' => [
                    'message' => 'By following the energy-saving tips for your devices, you could save up to 20-30% on energy costs.',
                    'estimated_annual_savings' => '$' . round($totalAnnualCost * 0.25, 2),
                ],
            ],
        ], 200);
    }

    /**
     * Extract average value from string range or single value
     */
    private function extractAverageValue(string $value): float
    {
        // Remove currency symbols and extra text
        $value = preg_replace('/[^0-9.\-]/', '', $value);

        // Check if it's a range (e.g., "1.2-2.0")
        if (strpos($value, '-') !== false) {
            $parts = explode('-', $value);
            if (count($parts) === 2) {
                return (floatval($parts[0]) + floatval($parts[1])) / 2;
            }
        }

        return floatval($value);
    }

    /**
     * Generate personalized recommendations
     */
    private function generateRecommendations($devices, $totalCost): array
    {
        $recommendations = [];

        // High cost warning
        if ($totalCost > 500) {
            $recommendations[] = [
                'type' => 'high_cost_alert',
                'title' => 'High Energy Consumption Detected',
                'message' => 'Your devices consume significant energy. Focus on the highest consumers first.',
                'priority' => 'high',
            ];
        }

        // Check for old heating system
        $hasOldHeating = $devices->where('device_category', 'air_conditioner')->count() > 2;
        if ($hasOldHeating) {
            $recommendations[] = [
                'type' => 'heating_upgrade',
                'title' => 'Consider Heat Pump Upgrade',
                'message' => 'Switching to a heat pump could reduce heating costs by up to 50%.',
                'priority' => 'medium',
            ];
        }

        // Refrigerator check
        $refrigerators = $devices->where('device_category', 'refrigerator');
        if ($refrigerators->count() > 1) {
            $recommendations[] = [
                'type' => 'appliance_optimization',
                'title' => 'Multiple Refrigerators Detected',
                'message' => 'Consider unplugging secondary refrigerators when not needed to save energy.',
                'priority' => 'medium',
            ];
        }

        // General tip
        $recommendations[] = [
            'type' => 'general_tip',
            'title' => 'Smart Power Strips',
            'message' => 'Use smart power strips to eliminate phantom power draw from electronics.',
            'priority' => 'low',
        ];

        return $recommendations;
    }
}
