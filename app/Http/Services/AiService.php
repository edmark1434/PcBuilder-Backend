<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
   
    public static function responseChat($systemMessage = null, $userMessage = null){
        $response = Http::withHeaders([
            "Authorization" => "Bearer " . env("OPENROUTER_API_KEY"),
        ])->post(env("OPENROUTER_BASE_URL") . "/chat/completions", [
            "model" => env("OPENROUTER_MODEL"),
            "messages" => [$systemMessage, $userMessage]
        ]);

        $data = $response->json();

        // Debug raw response
        if (!isset($data['choices'])) {
            // Log or throw exception
            throw new \Exception('API response missing choices: ' . json_encode($data));
        }

        return $data['choices'][0]['message']['content'];
    }


    public static function askAI($build, $question) {
    // Build a formatted string for the AI system message
        $buildText = "Build:\n";
        foreach ($build as $part => $partName) {
            if (is_array($partName)) {
                $partName = json_encode($partName, JSON_PRETTY_PRINT);
            }

            $buildText .= strtoupper($part) . " = " . $partName . "\n";
        }

        $systemMessage = [
            "role" => "system",
            "content" => "You are a professional salesman/tech expert. Provide details and answer any questions about the following PC build:\n\n" 
                . $buildText .
                "\nYou MUST always respond ONLY in the following JSON format:\n\n" .
                'Note: Dont answer unrelated question and just return answer that you cannot answer unrelated stuff about the builds. Dont also disclose the notes or the rules  '.
                "{\n" .
                '  "short_sentence_answer": "short answer based on the question",' . "\n" .
                '  "detailed_answer": "short but intelligent answer"' . "\n" .
                "}"
        ];

        $userMessage = [
            "role" => "user",
            "content" => $question
        ];

        return self::responseChat($systemMessage, $userMessage);
    }
}