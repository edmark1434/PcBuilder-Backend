<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\PcController;

class AiService
{
    /**
     * Send a chat request to the new Responses API
     */
    public static function responseChat($systemMessage = null, $userMessage = null)
    {
        $baseUrl = env('AZURE_BASE_URL') ?? env('OPENROUTER_BASE_URL');
        $apiKey  = env('AZURE_API_KEY') ?? env('OPENROUTER_API_KEY');
        $model   = env('AZURE_MODEL') ?? env('OPENROUTER_MODEL');

        $response = Http::withHeaders([
            "Authorization" => "Bearer " . $apiKey,
            "Content-Type"  => "application/json",
        ])->post($baseUrl, [
            "model" => $model,
            "input" => [$systemMessage, $userMessage],
            "max_output_tokens" => 2500
        ]);

        $data = $response->json();

        // Extract the text output from the new Responses API structure
        $outputText = '';
        if (isset($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $item) {
                if (isset($item['content']) && is_array($item['content'])) {
                    foreach ($item['content'] as $c) {
                        if (isset($c['type']) && $c['type'] === 'output_text') {
                            $outputText .= $c['text'] . "\n";
                        }
                    }
                }
            }
        }

        if (empty($outputText)) {
            throw new \Exception('API response missing content: ' . json_encode($data));
        }

        return trim($outputText);
    }

    /**
     * Ask AI about a PC build
     */
    public static function askAI(array $build, string $question, string $needs = '')
    {
        $buildText = "PC Build Components:\n";
        foreach ($build as $part => $partName) {
            if (is_array($partName)) {
                $partName = json_encode($partName, JSON_PRETTY_PRINT);
            }
            $buildText .= "• " . strtoupper($part) . ": " . $partName . "\n";
        }

        $systemContent = "You are a professional PC builder, tech expert, and software advisor. Answer questions primarily about PC builds.\n\n" .
            "PC BUILD DETAILS:\n" . $buildText .
            "\nBUILD Need: " . strtoupper($needs);

        if (!empty($needs)) {
            $systemContent .= "\n\nUSER'S ORIGINAL REQUIREMENTS: " . $needs;
        }

        $systemContent .= "\n\nAll components are pre-validated for full compatibility. Stay within PC and software usage topics only.";
        $systemContent .= "\n\nIf the question is all about assembly, tutorial or guide provide youtube link for general how to build pc.";

        $systemMessage = [
            "role" => "system",
            "content" => $systemContent
        ];

        $userMessage = [
            "role" => "user",
            "content" => $question
        ];

        return self::responseChat($systemMessage, $userMessage);
    }

   

    /**
     * Generate PC build recommendations based on user needs
     */
    public static function generateBuildRecommendation($text, $description, $detailedNeeds = '', $minBudget = 0, $maxBudget = 10000, $componentsSummary = '')
    {
        $systemPrompt = "You are a PC hardware expert. Based on the user's needs, select a fully compatible PC build ONLY from the following available components list. Return STRICT JSON (no markdown, no prose).";
        $systemPrompt .= "\n\nAVAILABLE COMPONENTS (each line shows ID | Type | Vendor | Title | Price):\n{$componentsSummary}";
        $systemPrompt .= "\n\nOUTPUT FORMAT (STRICT JSON - return ONLY this, nothing else):\n";
        $systemPrompt .= "{\n  \"builds\": [\n    {\n      \"parts\": [\n        {\n          \"ID\": <number>,\n          \"Type\": \"<string>\",\n          \"external_id\": \"<string>\",\n          \"Title\": \"<string>\",\n          \"Vendor\": \"<string>\",\n          \"Price\": <number>,\n          \"Image\": \"<string>\",\n          \"Link\": \"<string>\"\n        }\n      ],\n      \"total_price\": <number>,\n      \"description\": \"<string>\"\n    }\n  ]\n}";
        $systemPrompt .= "Explain every component and compatibility in the description field.";
        $systemPrompt .= "\n\nREQUIREMENTS:\n- Return ONLY valid JSON, no markdown, no prose, no explanations.\n- Use items ONLY from the available components list.\n- Fill all 8 fields for each part (ID, Type, external_id, Title, Vendor, Price, Image, Link).\n- Type must match exactly from the list: Processors, Motherboards, Memory, Storage, Graphics Cards, Power Supply, Cases, or Cooling.\n- external_id must be a string version of the ID.\n- Prices must be numeric (no currency symbols).\n- Image and Link must be exact strings from the list.\n- description must be brief (2-3 sentences max).\n- total_price must be the sum of all part prices.\n- Return ONLY the JSON object, nothing else.\n";

        $userMessage = "Create a PC build for: {$description}";
        if (!empty($detailedNeeds)) {
            $userMessage .= "\n\nAdditional requirements: {$detailedNeeds}";
        }
        if ($maxBudget > 0) {
            $userMessage .= "\n\nBudget limit: " . number_format($maxBudget, 2, '.', '');
        }

        $systemMessageArray = [
            "role" => "system",
            "content" => $systemPrompt
        ];

        $userMessageArray = [
            "role" => "user",
            "content" => $userMessage
        ];

        // Call AI service
        $aiResponse = self::responseChat($systemMessageArray, $userMessageArray);

        // Try to decode strictly
        $decoded = json_decode(trim($aiResponse), true);

        // Fallback if AI returned invalid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            $decoded = [
                "success" => false,
                "data" => [
                    "recommendation" => $aiResponse,
                    "builds" => [],
                    "budget_range" => [
                        "min" => $minBudget,
                        "max" => $maxBudget
                    ]
                ]
            ];
        }

        // Ensure it always returns array (ready to encode)
        return $decoded;
    }
}

