<?php

namespace App\Http\Controllers;

use App\Models\DeviceAnalysis;
use App\Services\GeminiVisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DeviceAnalysisController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiVisionService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Analyze device from uploaded image
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analyze(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,heic|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store uploaded image
            $image = $request->file('image');
            $imagePath = $image->store('device-images', 'public');
            $fullPath = storage_path('app/public/' . $imagePath);

            // Analyze device using Gemini Vision
            $aiResponse = $this->geminiService->analyzeDevice($fullPath);

            // Use fallback if AI fails
            if (!$aiResponse) {
                $aiResponse = $this->geminiService->getFallbackResponse();
            }

            // Save analysis to database (link to authenticated user)
            $analysis = DeviceAnalysis::create([
                'user_id' => $request->user()->id,
                'image_path' => $imagePath,
                'device_category' => $aiResponse['device']['category'] ?? 'unknown',
                'device_brand' => $aiResponse['device']['brand'] ?? 'Unknown',
                'device_model' => $aiResponse['device']['model'] ?? 'Unknown',
                'confidence_level' => $aiResponse['device']['confidence'] ?? 'low',
                'fallback_level' => $aiResponse['fallback_level'] ?? 'generic',
                'typical_wattage' => $aiResponse['energy']['typical_wattage'] ?? 'N/A',
                'daily_kwh' => $aiResponse['energy']['daily_kwh'] ?? 'N/A',
                'annual_kwh' => $aiResponse['energy']['annual_kwh'] ?? 'N/A',
                'estimated_annual_cost' => $aiResponse['energy']['estimated_annual_cost'] ?? 'N/A',
                'energy_saving_tips' => $aiResponse['tips'] ?? [],
                'raw_ai_response' => $aiResponse,
            ]);

            // Return formatted response
            return response()->json([
                'success' => true,
                'message' => 'Device analyzed successfully',
                'data' => [
                    'id' => $analysis->id,
                    'device' => [
                        'category' => $analysis->device_category,
                        'brand' => $analysis->device_brand,
                        'model' => $analysis->device_model,
                        'confidence' => $analysis->confidence_level,
                    ],
                    'energy' => [
                        'typical_wattage' => $analysis->typical_wattage,
                        'idle_wattage' => $aiResponse['energy']['idle_wattage'] ?? 'N/A',
                        'active_wattage' => $aiResponse['energy']['active_wattage'] ?? 'N/A',
                        'daily_kwh' => $analysis->daily_kwh,
                        'annual_kwh' => $analysis->annual_kwh,
                        'estimated_annual_cost' => $analysis->estimated_annual_cost,
                    ],
                    'tips' => $analysis->energy_saving_tips,
                    'fallback_level' => $analysis->fallback_level,
                    'reasoning' => $aiResponse['reasoning'] ?? null,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during analysis',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get analysis by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $analysis = DeviceAnalysis::find($id);

        if (!$analysis) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $analysis->id,
                'device' => [
                    'category' => $analysis->device_category,
                    'brand' => $analysis->device_brand,
                    'model' => $analysis->device_model,
                    'confidence' => $analysis->confidence_level,
                ],
                'energy' => [
                    'typical_wattage' => $analysis->typical_wattage,
                    'daily_kwh' => $analysis->daily_kwh,
                    'annual_kwh' => $analysis->annual_kwh,
                    'estimated_annual_cost' => $analysis->estimated_annual_cost,
                ],
                'tips' => $analysis->energy_saving_tips,
                'fallback_level' => $analysis->fallback_level,
                'analyzed_at' => $analysis->created_at->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Get list of supported device categories
     *
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'categories' => [
                'laptop',
                'desktop',
                'monitor',
                'tv',
                'refrigerator',
                'air_conditioner',
                'microwave',
                'washing_machine',
                'dryer',
                'dishwasher',
                'printer',
                'scanner',
                'router',
                'gaming_console',
                'other',
            ],
        ], 200);
    }

    /**
     * Get user's analysis history
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $analyses = DeviceAnalysis::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($analysis) {
                return [
                    'id' => $analysis->id,
                    'device' => [
                        'category' => $analysis->device_category,
                        'brand' => $analysis->device_brand,
                        'model' => $analysis->device_model,
                        'confidence' => $analysis->confidence_level,
                    ],
                    'energy' => [
                        'typical_wattage' => $analysis->typical_wattage,
                        'daily_kwh' => $analysis->daily_kwh,
                        'annual_kwh' => $analysis->annual_kwh,
                        'estimated_annual_cost' => $analysis->estimated_annual_cost,
                    ],
                    'fallback_level' => $analysis->fallback_level,
                    'analyzed_at' => $analysis->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $analyses->count(),
                'analyses' => $analyses,
            ],
        ], 200);
    }
}
