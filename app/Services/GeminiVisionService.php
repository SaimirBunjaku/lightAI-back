<?php

namespace App\Services;

use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class GeminiVisionService
{
    /**
     * Analyze device image using Gemini Vision API
     *
     * @param string $imagePath Path to the uploaded image
     * @return array|null Parsed AI response or null on failure
     */
    public function analyzeDevice(string $imagePath): ?array
    {
        try {
            // Read image and convert to base64
            $imageData = file_get_contents($imagePath);
            $base64Image = base64_encode($imageData);
            $mimeType = mime_content_type($imagePath);

            // Craft the prompt for device identification and energy analysis
            $prompt = $this->buildPrompt();

            // Call Gemini API with vision (using Gemini 2.0 Flash for vision capabilities)
            $result = Gemini::generativeModel(model: 'gemini-2.0-flash-exp')
                ->generateContent([
                    $prompt,
                    new Blob(
                        mimeType: MimeType::from($mimeType),
                        data: $base64Image
                    )
                ]);

            // Extract text response
            $responseText = $result->text();

            // Parse the JSON response from AI
            return $this->parseAIResponse($responseText);

        } catch (\Exception $e) {
            Log::error('Gemini Vision API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build comprehensive prompt for AI analysis
     *
     * @return string
     */
    private function buildPrompt(): string
    {
        return <<<PROMPT
You are an expert in electronic devices and energy efficiency. Analyze this image of an electronic device and provide comprehensive information to help users save energy.

Your task:
1. **Identify the device** - Determine the category, brand, and specific model if possible
2. **Provide energy consumption data** - Based on your knowledge of this device type/model
3. **Generate energy-saving tips** - Provide 5 specific, actionable tips for THIS device

**Response Format** (MUST be valid JSON):
{
  "device": {
    "category": "laptop|desktop|refrigerator|tv|monitor|air_conditioner|microwave|printer|scanner|router|gaming_console|washing_machine|dryer|dishwasher|other",
    "brand": "Apple|Samsung|LG|Dell|HP|etc or Unknown",
    "model": "Specific model name/number or Unknown",
    "confidence": "high|medium|low"
  },
  "energy": {
    "typical_wattage": "Range in watts (e.g., '30-50W' or '100W')",
    "idle_wattage": "Watts when idle/standby if applicable",
    "active_wattage": "Watts during typical use",
    "daily_kwh": "Estimated kWh per day (e.g., '0.24-0.40')",
    "annual_kwh": "Estimated kWh per year (e.g., '87.6-146')",
    "estimated_annual_cost": "Cost in USD assuming \$0.15/kWh (e.g., '\$13-22')"
  },
  "tips": [
    "Tip 1: Specific to this device model/category",
    "Tip 2: Focus on immediate energy savings",
    "Tip 3: Include settings optimization",
    "Tip 4: Behavioral changes for efficiency",
    "Tip 5: Long-term energy-saving practices"
  ],
  "fallback_level": "specific|category|generic",
  "reasoning": "Brief explanation of confidence level and any assumptions made"
}

**Important Guidelines:**
- If you can identify the EXACT model → use "fallback_level": "specific" and provide model-specific data
- If you can only identify the CATEGORY (e.g., "it's a laptop but can't tell which model") → use "fallback_level": "category" and provide category-level averages
- If the image is unclear → use "fallback_level": "generic", set confidence to "low", and provide general device tips
- Base energy estimates on real-world data for that device type/model
- Tips should be practical, easy to implement, and impactful for energy savings
- Focus on helping users reduce their carbon footprint and save money

Return ONLY the JSON object, no additional text or markdown formatting.
PROMPT;
    }

    /**
     * Parse AI response text into structured array
     *
     * @param string $responseText
     * @return array|null
     */
    private function parseAIResponse(string $responseText): ?array
    {
        try {
            // Remove markdown code blocks if present
            $responseText = preg_replace('/```json\s*/', '', $responseText);
            $responseText = preg_replace('/```\s*$/', '', $responseText);
            $responseText = trim($responseText);

            // Decode JSON
            $data = json_decode($responseText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON Parse Error: ' . json_last_error_msg());
                Log::error('Raw Response: ' . $responseText);
                return null;
            }

            // Validate required fields
            if (!isset($data['device']) || !isset($data['energy']) || !isset($data['tips'])) {
                Log::error('Missing required fields in AI response');
                return null;
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Response parsing error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate fallback response when AI fails
     *
     * @return array
     */
    public function getFallbackResponse(): array
    {
        return [
            'device' => [
                'category' => 'unknown',
                'brand' => 'Unknown',
                'model' => 'Unknown',
                'confidence' => 'low'
            ],
            'energy' => [
                'typical_wattage' => 'Unable to determine',
                'idle_wattage' => 'N/A',
                'active_wattage' => 'N/A',
                'daily_kwh' => 'Unable to determine',
                'annual_kwh' => 'Unable to determine',
                'estimated_annual_cost' => 'Unable to determine'
            ],
            'tips' => [
                'Unplug devices when not in use to eliminate phantom power draw',
                'Use smart power strips to easily cut power to multiple devices',
                'Enable energy-saving modes available on most electronic devices',
                'Keep devices clean and well-maintained for optimal efficiency',
                'Consider upgrading to ENERGY STAR certified devices when replacing old equipment'
            ],
            'fallback_level' => 'generic',
            'reasoning' => 'Could not identify the device from the image. Please try taking a clearer photo with better lighting, or ensure the device is fully visible.'
        ];
    }
}
