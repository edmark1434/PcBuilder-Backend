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
            "messages" => [$systemMessage, $userMessage],
            "temperature" => 0.7,
            "max_tokens" => 1500
        ]);

        $data = $response->json();

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
        $buildText = "PC Build Components:\n";
        foreach ($build as $part => $partName) {
            if (is_array($partName)) {
                $partName = json_encode($partName, JSON_PRETTY_PRINT);
            }
            $buildText .= "• " . strtoupper($part) . ": " . $partName . "\n";
        }

        // Detect question type
        $lowerQuestion = strtolower($question);
        $isGuideline = preg_match('/guide|tutorial|steps|how to|procedure|process|instructions/i', $lowerQuestion);
        $isCompatibility = preg_match('/compat|fit|work.*together|match|suitable/i', $lowerQuestion);
        $isComparison = preg_match('/compare|vs|versus|difference|better|worse/i', $lowerQuestion);
        $isAlternative = preg_match('/alternative|option|other choice|instead|substitute/i', $lowerQuestion);
        $isPerformance = preg_match('/performance|speed|fps|benchmark|score|rating/i', $lowerQuestion);
        $isUpgrade = preg_match('/upgrade|improve|enhance|future proof/i', $lowerQuestion);
        $isCost = preg_match('/cost|price|budget|cheaper|expensive|value/i', $lowerQuestion);

        $systemMessage = [
            "role" => "system",
            "content" =>
                "You are a professional PC builder and tech expert. Answer questions about this PC build:\n\n" .
                $buildText .
                "\nINSTRUCTIONS:\n" .
                "\nThe build given is already compatible in each others component. Psu is already sustained the given builds.\n" .
                "1. FOR GUIDELINES/INSTRUCTIONS QUESTIONS:\n" .
                "   - Provide bulleted step-by-step instructions\n" .
                "   - Format: • Step 1: ...\n   • Step 2: ...\n" .
                "   - Start with 'GUIDE:' then bullet points\n\n" .
                "2. FOR COMPATIBILITY QUESTIONS:\n" .
                "   - Provide a compatibility score (0-100%)\n" .
                "   - List specific compatibility bullet points\n" .
                "   - Mention potential issues\n" .
                "   - Format: Compatibility Score: XX%\n   • Point 1\n   • Point 2\n\n" .
                "3. FOR COMPARISON/ALTERNATIVE QUESTIONS:\n" .
                "   - Use bullet points for pros/cons\n" .
                "   - Format: • Pros: ...\n   • Cons: ...\n" .
                "   - Include specific component suggestions\n\n" .
                "4. FOR PERFORMANCE QUESTIONS:\n" .
                "   - Provide expected performance metrics\n" .
                "   - Use bullet points for different scenarios\n" .
                "   - Format: • Gaming: XX FPS at YY resolution\n   • Productivity: ...\n\n" .
                "5. FOR YES/NO QUESTIONS:\n" .
                "   - Start with direct answer: YES or NO\n" .
                "   - Then provide bulleted explanation\n" .
                "   - Format: YES/NO\n   • Reason 1\n   • Reason 2\n\n" .
                "6. FOR UPGRADE/IMPROVEMENT QUESTIONS:\n" .
                "   - Provide prioritized bulleted upgrade path\n" .
                "   - Format: Upgrade Priority:\n   • 1. Component (Reason)\n   • 2. Component (Reason)\n\n" .
                "7. FOR COST/PRICE QUESTIONS:\n" .
                "   - Provide bulleted value analysis\n" .
                "   - Suggest cost-effective alternatives\n" .
                "   - Format: Value Assessment:\n   • Point 1\n   • Point 2\n\n" .
                "GENERAL RULES:\n" .
                "- Always use bullet points (•) for lists\n" .
                "- Keep explanations concise but informative\n" .
                "- Be specific about components\n" .
                "- If question is unrelated, respond: 'This question is not related to the PC build.'\n" .
                "- NEVER use numbered lists, always use bullet points\n" .
                "- Use markdown-like formatting with * for emphasis if needed"
        ];

        $userMessage = [
            "role" => "user",
            "content" => $question
        ];

        return self::responseChat($systemMessage, $userMessage);
    }
}