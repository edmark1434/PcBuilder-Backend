<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    /**
     * Send a chat request to the OpenRouter API
     */
    public static function responseChat($systemMessage = null, $userMessage = null)
    {
        $baseUrl = env('AZURE_BASE_URL') ?? env('OPENROUTER_BASE_URL');
        $apiKey = env('AZURE_API_KEY') ?? env('OPENROUTER_API_KEY');
        $model  = env('AZURE_MODEL') ?? env('OPENROUTER_MODEL');

        $response = Http::withHeaders([
            "Authorization" => "Bearer " . $apiKey,
        ])->post($baseUrl, [
            "model" => $model,
            "messages" => [$systemMessage, $userMessage]
        ]);

        $data = $response->json();

        // Check for errors
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \Exception('API response missing content: ' . json_encode($data));
        }

        return $data['choices'][0]['message']['content'];
    }

    /**
     * Ask AI about a PC build
     */
    public static function askAI(array $build, string $question)
    {
        // Format the build into a readable string
        $buildText = "Build Details:\n";
        foreach ($build as $part => $partName) {
            if (is_array($partName)) {
                $partName = json_encode($partName, JSON_PRETTY_PRINT);
            }
            $buildText .= strtoupper($part) . " = " . $partName . "\n";
        }

        // System message instructing AI to answer yes/no when possible
        $systemMessage = [
            "role" => "system",
            "content" =>
                "You are a professional PC salesman and tech expert. Answer questions about the following PC build:\n\n" .
                $buildText .
                "\nInstructions:\n" .
                "- If the question can be answered with 'Yes' or 'No', provide that as 'direct_answer'.\n" .
                "- Always provide a detailed explanation in 'detailed_answer'.\n" .
                "- Respond ONLY in strict JSON format, no extra text.\n" .
                "- Ignore unrelated questions and respond with 'Cannot answer unrelated question'.\n" .
                "JSON format example:\n" .
                "{\n" .
                '  "direct_answer": "Yes or No or N/A",' . "\n" .
                '  "detailed_answer": "Provide a short but informative explanation"' . "\n" .
                "}"
        ];

        $userMessage = [
            "role" => "user",
            "content" => $question
        ];

        return self::responseChat($systemMessage, $userMessage);
    }
}
