<?php

namespace App\Services;

use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class GeminiBillScannerService
{
    /**
     * Analyze KEDS electricity bill using Gemini Vision API
     *
     * @param string $imagePath Path to the uploaded bill image
     * @return array|null Parsed AI response or null on failure
     */
    public function analyzeBill(string $imagePath): ?array
    {
        try {
            // Read image and convert to base64
            $imageData = file_get_contents($imagePath);
            $base64Image = base64_encode($imageData);
            $mimeType = mime_content_type($imagePath);

            // Craft the prompt for KEDS bill OCR and analysis
            $prompt = $this->buildPrompt();

            // Call Gemini API with vision
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
            Log::error('Gemini Bill Scanner API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build comprehensive prompt for KEDS bill OCR and analysis
     *
     * @return string
     */
    private function buildPrompt(): string
    {
        return <<<PROMPT
You are an expert at reading Kosovo electricity bills from KEDS (Kosovo Energy Distribution Services). Analyze this KEDS electricity bill image and extract all the data accurately.

**Your task:**
1. **Extract all consumption data** from the table (Monthly consumption / Mese&#263;na potro&#353;nja)
2. **Extract all pricing data** (Price € / Cena)
3. **Extract all cost data** (Amount € / Iznos)
4. **Translate the tariff codes** into human-readable explanations
5. **Provide insights** about the consumption patterns

**Kosovo KEDS Tariff System Explanation:**
- **A1/B1**: Daytime consumption (standard rate) - €0.0779/kWh (7.79 cents)
- **A2/B1**: Nighttime consumption (22:00-06:00, cheaper) - €0.0334/kWh (3.34 cents)
- **A1/B2**: Peak daytime consumption (higher rate) - €0.1445/kWh (14.45 cents)
- **A2/B2**: Peak nighttime consumption - €0.0681/kWh (6.81 cents)
- **Tarifa Fikse / Standing Charge**: Fixed monthly charge (usually €2)
- **TVSH / VAT**: Value Added Tax (8%)

**IMPORTANT**: In KEDS bills, the "Price (€)" column may show values like "7.79" or "3.34" - these represent cents, so the actual price per kWh is €0.0779 or €0.0334. Always verify by checking if consumption × price = amount shown.

**Response Format** (MUST be valid JSON):
{
  "extraction": {
    "bill_month": "Extract the billing period/month",
    "consumption": {
      "a1_b1_kwh": "Extract kWh value for A1/B1 row",
      "a2_b1_kwh": "Extract kWh value for A2/B1 row",
      "a1_b2_kwh": "Extract kWh value for A1/B2 row",
      "a2_b2_kwh": "Extract kWh value for A2/B2 row",
      "total_kwh": "Calculate or extract total kWh"
    },
    "pricing": {
      "price_a1_b1": "Extract price for A1/B1",
      "price_a2_b1": "Extract price for A2/B1",
      "price_a1_b2": "Extract price for A1/B2",
      "price_a2_b2": "Extract price for A2/B2"
    },
    "costs": {
      "amount_a1_b1": "Extract cost for A1/B1",
      "amount_a2_b1": "Extract cost for A2/B1",
      "amount_a1_b2": "Extract cost for A1/B2",
      "amount_a2_b2": "Extract cost for A2/B2",
      "standing_charge": "Extract Tarifa Fikse/Standing Charge",
      "net_total": "Extract Neto/Net",
      "vat": "Extract TVSH/VAT",
      "bill_total": "Extract Total bill amount",
      "kesco_debt": "Extract KESCO debt if present, otherwise 0"
    }
  },
  "human_readable_breakdown": {
    "summary": "Brief summary in English: 'You consumed X kWh this month, mostly during [daytime/nighttime], costing €Y total'",
    "tariff_explanation": {
      "a1_b1": "If this tariff was used, explain: 'Daytime usage (standard rate): X kWh at €0.0779/kWh = €Y'",
      "a2_b1": "If this tariff was used, explain: 'Nighttime usage (cheaper rate 22:00-06:00): X kWh at €0.0334/kWh = €Y'",
      "a1_b2": "If applicable",
      "a2_b2": "If applicable"
    },
    "breakdown_in_albanian": "Provide the same breakdown in Albanian (Shqip) for Kosovo users"
  },
  "insights": [
    "Insight 1: Analyze if user is using mostly daytime vs nighttime power",
    "Insight 2: Recommend time-of-use strategies (e.g., 'Run washing machine after 22:00 to save money')",
    "Insight 3: Compare to average Kosovo household consumption",
    "Insight 4: Identify potential savings opportunities",
    "Insight 5: Seasonal advice (heating in winter, cooling in summer)"
  ],
  "device_estimates": {
    "note": "Based on total kWh, provide rough estimates of device contributions",
    "estimated_breakdown": {
      "heating_cooling": "Estimated % and kWh",
      "refrigeration": "Estimated % and kWh",
      "lighting": "Estimated % and kWh",
      "electronics": "Estimated % and kWh",
      "other": "Estimated % and kWh"
    }
  },
  "confidence": "high|medium|low - How confident are you in the OCR accuracy?",
  "warnings": ["Any warnings about data quality, missing fields, or unclear values"]
}

**Important Guidelines:**
- Extract EXACT numbers from the bill - don't estimate
- If a field is "0" or blank in the bill, use 0 or null
- Pay attention to decimal points (Kosovo uses dots for decimals: 7.79)
- The bill is bilingual (English/Albanian) - extract from whichever is clearer
- Be precise with the math - totals should add up
- Provide actionable insights specific to Kosovo electricity pricing
- Insights should help users save money on their next bill

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
            if (!isset($data['extraction']) || !isset($data['human_readable_breakdown'])) {
                Log::error('Missing required fields in AI bill scan response');
                return null;
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Bill response parsing error: ' . $e->getMessage());
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
            'extraction' => [
                'bill_month' => 'Unknown',
                'consumption' => [
                    'a1_b1_kwh' => 0,
                    'a2_b1_kwh' => 0,
                    'a1_b2_kwh' => 0,
                    'a2_b2_kwh' => 0,
                    'total_kwh' => 0,
                ],
                'pricing' => [
                    'price_a1_b1' => 0.0779,
                    'price_a2_b1' => 0.0334,
                    'price_a1_b2' => 0.1445,
                    'price_a2_b2' => 0.0681,
                ],
                'costs' => [
                    'amount_a1_b1' => 0,
                    'amount_a2_b1' => 0,
                    'amount_a1_b2' => 0,
                    'amount_a2_b2' => 0,
                    'standing_charge' => 0,
                    'net_total' => 0,
                    'vat' => 0,
                    'bill_total' => 0,
                    'kesco_debt' => 0,
                ],
            ],
            'human_readable_breakdown' => [
                'summary' => 'Unable to read the bill. Please try taking a clearer photo.',
                'tariff_explanation' => [],
                'breakdown_in_albanian' => 'Nuk mund të lexohet fatura. Ju lutemi provoni një fotografi më të qartë.',
            ],
            'insights' => [
                'Could not analyze the bill image',
                'Please ensure the bill is clearly visible',
                'Try taking the photo in good lighting',
                'Make sure all text is readable',
            ],
            'device_estimates' => null,
            'confidence' => 'low',
            'warnings' => ['Failed to extract data from the bill image'],
        ];
    }
}
