<?php

namespace App\Http\Controllers;

use App\Models\KedsBillAnalysis;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get user's devices
        $devices = UserDevice::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        // Get user's bills
        $bills = KedsBillAnalysis::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate total monthly device costs
        $totalDeviceCost = 0;
        $totalDailyKwh = 0;

        foreach ($devices as $device) {
            if ($device->estimated_annual_cost) {
                $annualCost = floatval(preg_replace('/[^0-9.]/', '', $device->estimated_annual_cost));
                $totalDeviceCost += $annualCost / 12; // Monthly cost
            }

            if ($device->daily_kwh) {
                $dailyKwh = floatval(preg_replace('/[^0-9.]/', '', $device->daily_kwh));
                $totalDailyKwh += $dailyKwh;
            }
        }

        // Get latest bill data
        $latestBill = $bills->first();
        $previousBill = $bills->skip(1)->first();

        // Calculate trends
        $consumptionTrend = null;
        $costTrend = null;

        if ($latestBill && $previousBill) {
            if ($previousBill->total_kwh > 0) {
                $consumptionTrend = (($latestBill->total_kwh - $previousBill->total_kwh) / $previousBill->total_kwh) * 100;
            }

            if ($previousBill->bill_total > 0) {
                $costTrend = (($latestBill->bill_total - $previousBill->bill_total) / $previousBill->bill_total) * 100;
            }
        }

        // Device category breakdown
        $categoryBreakdown = $devices->groupBy('device_category')->map(function ($categoryDevices) {
            $categoryCost = 0;
            $categoryKwh = 0;

            foreach ($categoryDevices as $device) {
                if ($device->estimated_annual_cost) {
                    $annualCost = floatval(preg_replace('/[^0-9.]/', '', $device->estimated_annual_cost));
                    $categoryCost += $annualCost / 12;
                }

                if ($device->daily_kwh) {
                    $dailyKwh = floatval(preg_replace('/[^0-9.]/', '', $device->daily_kwh));
                    $categoryKwh += $dailyKwh * 30; // Monthly
                }
            }

            return [
                'count' => $categoryDevices->count(),
                'monthly_cost' => '€' . number_format($categoryCost, 2),
                'monthly_kwh' => number_format($categoryKwh, 2),
            ];
        });

        // Top 3 most expensive devices
        $topDevices = $devices->sortByDesc(function ($device) {
            if ($device->estimated_annual_cost) {
                return floatval(preg_replace('/[^0-9.]/', '', $device->estimated_annual_cost));
            }
            return 0;
        })->take(3)->map(function ($device) {
            return [
                'id' => $device->id,
                'name' => $device->device_name,
                'category' => $device->device_category,
                'monthly_cost' => $device->estimated_annual_cost
                    ? '€' . number_format(floatval(preg_replace('/[^0-9.]/', '', $device->estimated_annual_cost)) / 12, 2)
                    : 'N/A',
                'daily_kwh' => $device->daily_kwh ?? 'N/A',
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_devices' => $devices->count(),
                    'total_bills_analyzed' => $bills->count(),
                    'estimated_monthly_cost' => '€' . number_format($totalDeviceCost, 2),
                    'estimated_monthly_kwh' => number_format($totalDailyKwh * 30, 2),
                ],
                'latest_bill' => $latestBill ? [
                    'id' => $latestBill->id,
                    'month' => $latestBill->bill_month,
                    'total_kwh' => $latestBill->total_kwh,
                    'total_cost' => '€' . number_format($latestBill->bill_total, 2),
                    'analyzed_at' => $latestBill->created_at->toIso8601String(),
                    'consumption_trend' => $consumptionTrend ? [
                        'percentage' => round($consumptionTrend, 1),
                        'direction' => $consumptionTrend > 0 ? 'up' : 'down',
                        'message' => $consumptionTrend > 0
                            ? 'Your consumption increased by ' . abs(round($consumptionTrend, 1)) . '% compared to last month'
                            : 'Your consumption decreased by ' . abs(round($consumptionTrend, 1)) . '% compared to last month',
                    ] : null,
                    'cost_trend' => $costTrend ? [
                        'percentage' => round($costTrend, 1),
                        'direction' => $costTrend > 0 ? 'up' : 'down',
                        'message' => $costTrend > 0
                            ? 'Your bill increased by ' . abs(round($costTrend, 1)) . '% compared to last month'
                            : 'Your bill decreased by ' . abs(round($costTrend, 1)) . '% compared to last month',
                    ] : null,
                ] : null,
                'device_breakdown' => [
                    'by_category' => $categoryBreakdown,
                    'top_consumers' => $topDevices,
                ],
                'insights' => $this->generateInsights($devices, $latestBill, $totalDeviceCost),
                'quick_stats' => [
                    'daytime_usage' => $latestBill ? [
                        'kwh' => $latestBill->a1_b1_kwh,
                        'cost' => '€' . number_format($latestBill->amount_a1_b1, 2),
                        'percentage' => $latestBill->total_kwh > 0 ? round(($latestBill->a1_b1_kwh / $latestBill->total_kwh) * 100, 1) : 0,
                    ] : null,
                    'nighttime_usage' => $latestBill ? [
                        'kwh' => $latestBill->a2_b1_kwh,
                        'cost' => '€' . number_format($latestBill->amount_a2_b1, 2),
                        'percentage' => $latestBill->total_kwh > 0 ? round(($latestBill->a2_b1_kwh / $latestBill->total_kwh) * 100, 1) : 0,
                    ] : null,
                ],
            ],
        ], 200);
    }

    /**
     * Generate personalized insights based on user data
     *
     * @param $devices
     * @param $latestBill
     * @param float $totalDeviceCost
     * @return array
     */
    private function generateInsights($devices, $latestBill, float $totalDeviceCost): array
    {
        $insights = [];

        // Insight 1: Device count
        if ($devices->count() == 0) {
            $insights[] = [
                'type' => 'action',
                'title' => 'Start Tracking Devices',
                'message' => 'Scan your devices to see which ones are using the most energy!',
                'icon' => '📸',
            ];
        } elseif ($devices->count() < 5) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Add More Devices',
                'message' => 'You have ' . $devices->count() . ' devices tracked. Scan more devices to get a complete picture of your energy usage.',
                'icon' => '📱',
            ];
        }

        // Insight 2: Bill analysis
        if (!$latestBill) {
            $insights[] = [
                'type' => 'action',
                'title' => 'Upload Your KEDS Bill',
                'message' => 'Upload a photo of your electricity bill to see detailed breakdowns and insights.',
                'icon' => '📄',
            ];
        } else {
            // Check daytime vs nighttime usage
            $daytimePercentage = $latestBill->total_kwh > 0 ? ($latestBill->a1_b1_kwh / $latestBill->total_kwh) * 100 : 0;

            if ($daytimePercentage > 70) {
                $potentialSavings = ($latestBill->a1_b1_kwh * 0.3) * (0.0779 - 0.0334);
                $insights[] = [
                    'type' => 'savings',
                    'title' => 'Shift to Nighttime Usage',
                    'message' => 'You use ' . round($daytimePercentage, 1) . '% of your electricity during the day. Try running heavy appliances (washing machine, dishwasher) between 22:00-06:00 to save approximately €' . number_format($potentialSavings, 2) . ' per month.',
                    'icon' => '🌙',
                ];
            }
        }

        // Insight 3: High-cost devices
        $highCostDevices = $devices->filter(function ($device) {
            if ($device->estimated_annual_cost) {
                $annualCost = floatval(preg_replace('/[^0-9.]/', '', $device->estimated_annual_cost));
                return ($annualCost / 12) > 10; // More than €10/month
            }
            return false;
        });

        if ($highCostDevices->count() > 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'High Energy Consumers Detected',
                'message' => 'You have ' . $highCostDevices->count() . ' device(s) costing over €10/month. Consider energy-efficient alternatives or adjust usage patterns.',
                'icon' => '⚠️',
            ];
        }

        // Insight 4: Seasonal advice
        $currentMonth = now()->month;
        if (in_array($currentMonth, [12, 1, 2])) {
            $insights[] = [
                'type' => 'seasonal',
                'title' => 'Winter Energy Tips',
                'message' => 'Winter bills are typically higher due to heating. Set your heater to 20-21°C and use a timer to avoid running it overnight.',
                'icon' => '❄️',
            ];
        } elseif (in_array($currentMonth, [6, 7, 8])) {
            $insights[] = [
                'type' => 'seasonal',
                'title' => 'Summer Energy Tips',
                'message' => 'Keep your fridge efficient by not overfilling it and ensuring the door seals properly. This can save up to €5/month.',
                'icon' => '☀️',
            ];
        }

        return $insights;
    }
}
