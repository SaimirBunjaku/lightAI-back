<?php

namespace App\Http\Controllers;

use App\Models\KedsBillAnalysis;
use App\Models\UserDevice;
use App\Services\GeminiBillScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class KedsBillController extends Controller
{
    protected $billScannerService;

    public function __construct(GeminiBillScannerService $billScannerService)
    {
        $this->billScannerService = $billScannerService;
    }

    /**
     * Upload and analyze KEDS electricity bill
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function scan(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'bill_image' => 'required|image|mimes:jpeg,png,jpg,heic|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store uploaded bill image
            $image = $request->file('bill_image');
            $imagePath = $image->store('keds-bills', 'public');
            $fullPath = storage_path('app/public/' . $imagePath);

            // Analyze bill using Gemini Vision
            $aiResponse = $this->billScannerService->analyzeBill($fullPath);

            // Use fallback if AI fails
            if (!$aiResponse) {
                $aiResponse = $this->billScannerService->getFallbackResponse();
            }

            // Extract data from AI response
            $extraction = $aiResponse['extraction'] ?? [];
            $consumption = $extraction['consumption'] ?? [];
            $pricing = $extraction['pricing'] ?? [];
            $costs = $extraction['costs'] ?? [];

            // Save bill analysis to database
            $billAnalysis = KedsBillAnalysis::create([
                'user_id' => $request->user()->id,
                'bill_image_path' => $imagePath,
                'bill_month' => $extraction['bill_month'] ?? null,

                // Consumption
                'total_kwh' => $consumption['total_kwh'] ?? 0,
                'a1_b1_kwh' => $consumption['a1_b1_kwh'] ?? 0,
                'a2_b1_kwh' => $consumption['a2_b1_kwh'] ?? 0,
                'a1_b2_kwh' => $consumption['a1_b2_kwh'] ?? 0,
                'a2_b2_kwh' => $consumption['a2_b2_kwh'] ?? 0,

                // Pricing
                'price_a1_b1' => $pricing['price_a1_b1'] ?? 0.0779,
                'price_a2_b1' => $pricing['price_a2_b1'] ?? 0.0334,
                'price_a1_b2' => $pricing['price_a1_b2'] ?? 0.1445,
                'price_a2_b2' => $pricing['price_a2_b2'] ?? 0.0681,

                // Costs
                'amount_a1_b1' => $costs['amount_a1_b1'] ?? 0,
                'amount_a2_b1' => $costs['amount_a2_b1'] ?? 0,
                'amount_a1_b2' => $costs['amount_a1_b2'] ?? 0,
                'amount_a2_b2' => $costs['amount_a2_b2'] ?? 0,
                'standing_charge' => $costs['standing_charge'] ?? 0,
                'net_total' => $costs['net_total'] ?? 0,
                'vat' => $costs['vat'] ?? 0,
                'bill_total' => $costs['bill_total'] ?? 0,
                'kesco_debt' => $costs['kesco_debt'] ?? 0,

                // AI insights
                'human_readable_breakdown' => $aiResponse['human_readable_breakdown'] ?? [],
                'device_cost_estimates' => $aiResponse['device_estimates'] ?? null,
                'insights' => $aiResponse['insights'] ?? [],
                'raw_ai_response' => $aiResponse,
            ]);

            // Get user's saved devices to correlate with bill
            $userDevices = UserDevice::where('user_id', $request->user()->id)
                ->where('is_active', true)
                ->get();

            // Return formatted response
            return response()->json([
                'success' => true,
                'message' => 'KEDS bill analyzed successfully',
                'data' => [
                    'bill_id' => $billAnalysis->id,
                    'month' => $billAnalysis->bill_month,
                    'total_consumption' => [
                        'kwh' => $billAnalysis->total_kwh,
                        'cost' => '€' . number_format($billAnalysis->bill_total, 2),
                    ],
                    'breakdown' => [
                        'daytime' => [
                            'kwh' => $billAnalysis->a1_b1_kwh,
                            'price_per_kwh' => '€' . $billAnalysis->price_a1_b1,
                            'cost' => '€' . number_format($billAnalysis->amount_a1_b1, 2),
                            'explanation' => 'Standard daytime rate',
                        ],
                        'nighttime' => [
                            'kwh' => $billAnalysis->a2_b1_kwh,
                            'price_per_kwh' => '€' . $billAnalysis->price_a2_b1,
                            'cost' => '€' . number_format($billAnalysis->amount_a2_b1, 2),
                            'explanation' => 'Cheaper nighttime rate (22:00-06:00)',
                        ],
                        'standing_charge' => '€' . number_format($billAnalysis->standing_charge, 2),
                        'vat' => '€' . number_format($billAnalysis->vat, 2),
                    ],
                    'human_readable' => $billAnalysis->human_readable_breakdown,
                    'insights' => $billAnalysis->insights,
                    'device_estimates' => $billAnalysis->device_cost_estimates,
                    'your_devices' => [
                        'count' => $userDevices->count(),
                        'estimated_total_cost' => $this->estimateTotalDeviceCost($userDevices),
                        'message' => $userDevices->count() > 0
                            ? 'Based on your scanned devices, we estimate they account for approximately ' . $this->calculateDevicePercentage($userDevices, $billAnalysis->total_kwh) . '% of your consumption.'
                            : 'Scan your devices to see which ones are using the most energy!',
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while analyzing the bill',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all user's bill analyses
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $bills = KedsBillAnalysis::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'month' => $bill->bill_month,
                    'total_kwh' => $bill->total_kwh,
                    'total_cost' => '€' . number_format($bill->bill_total, 2),
                    'analyzed_at' => $bill->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $bills->count(),
                'bills' => $bills,
            ],
        ], 200);
    }

    /**
     * Get specific bill details
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $bill = KedsBillAnalysis::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$bill) {
            return response()->json([
                'success' => false,
                'message' => 'Bill not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'bill' => [
                    'id' => $bill->id,
                    'month' => $bill->bill_month,
                    'total_kwh' => $bill->total_kwh,
                    'total_cost' => '€' . number_format($bill->bill_total, 2),
                    'breakdown' => [
                        'daytime_kwh' => $bill->a1_b1_kwh,
                        'nighttime_kwh' => $bill->a2_b1_kwh,
                        'net_total' => '€' . number_format($bill->net_total, 2),
                        'vat' => '€' . number_format($bill->vat, 2),
                        'standing_charge' => '€' . number_format($bill->standing_charge, 2),
                    ],
                    'human_readable' => $bill->human_readable_breakdown,
                    'insights' => $bill->insights,
                    'device_estimates' => $bill->device_cost_estimates,
                    'analyzed_at' => $bill->created_at->toIso8601String(),
                ],
            ],
        ], 200);
    }

    /**
     * Get detailed breakdown of a bill
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function breakdown(int $id, Request $request): JsonResponse
    {
        $bill = KedsBillAnalysis::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$bill) {
            return response()->json([
                'success' => false,
                'message' => 'Bill not found',
            ], 404);
        }

        // Get user's devices for correlation
        $userDevices = UserDevice::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'consumption_breakdown' => [
                    'total_kwh' => $bill->total_kwh,
                    'tariffs' => [
                        [
                            'name' => 'A1/B1 - Daytime (Standard)',
                            'kwh' => $bill->a1_b1_kwh,
                            'price_per_kwh' => '€' . $bill->price_a1_b1,
                            'total_cost' => '€' . number_format($bill->amount_a1_b1, 2),
                            'percentage' => $bill->total_kwh > 0 ? round(($bill->a1_b1_kwh / $bill->total_kwh) * 100, 1) : 0,
                        ],
                        [
                            'name' => 'A2/B1 - Nighttime (22:00-06:00)',
                            'kwh' => $bill->a2_b1_kwh,
                            'price_per_kwh' => '€' . $bill->price_a2_b1,
                            'total_cost' => '€' . number_format($bill->amount_a2_b1, 2),
                            'percentage' => $bill->total_kwh > 0 ? round(($bill->a2_b1_kwh / $bill->total_kwh) * 100, 1) : 0,
                        ],
                    ],
                ],
                'cost_breakdown' => [
                    'energy_costs' => '€' . number_format($bill->amount_a1_b1 + $bill->amount_a2_b1, 2),
                    'standing_charge' => '€' . number_format($bill->standing_charge, 2),
                    'subtotal' => '€' . number_format($bill->net_total, 2),
                    'vat' => '€' . number_format($bill->vat, 2),
                    'total' => '€' . number_format($bill->bill_total, 2),
                ],
                'your_devices' => $userDevices->map(function ($device) {
                    return [
                        'name' => $device->device_name,
                        'category' => $device->device_category,
                        'estimated_monthly_cost' => $device->estimated_annual_cost
                            ? '€' . number_format(floatval(preg_replace('/[^0-9.]/', '', $device->estimated_annual_cost)) / 12, 2)
                            : 'N/A',
                    ];
                }),
                'recommendations' => $bill->insights,
            ],
        ], 200);
    }

    /**
     * Helper: Estimate total cost from user's devices
     */
    private function estimateTotalDeviceCost($devices): string
    {
        $total = 0;
        foreach ($devices as $device) {
            if ($device->estimated_annual_cost) {
                $annualCost = floatval(preg_replace('/[^0-9.]/', '', $device->estimated_annual_cost));
                $total += $annualCost / 12; // Monthly cost
            }
        }
        return '€' . number_format($total, 2);
    }

    /**
     * Helper: Calculate what percentage of the bill the devices account for
     */
    private function calculateDevicePercentage($devices, $totalKwh): int
    {
        if ($totalKwh == 0) return 0;

        $deviceKwh = 0;
        foreach ($devices as $device) {
            if ($device->daily_kwh) {
                $dailyKwh = floatval(preg_replace('/[^0-9.]/', '', $device->daily_kwh));
                $deviceKwh += $dailyKwh * 30; // Approximate monthly
            }
        }

        return $deviceKwh > 0 ? min(100, round(($deviceKwh / $totalKwh) * 100)) : 0;
    }
}
