<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\PcController;
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
    public static function askAI(array $build, string $question, string $category)
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
        $isAssembly = preg_match('/assemble|assembly|put together|build.*pc|construct|install|setup|set up/i', $lowerQuestion);
        $isTutorial = preg_match('/tutorial|learn|teaching|demonstrate|show.*how|watch.*video/i', $lowerQuestion);

        $systemMessage = [
        "role" => "system",
        "content" =>
            "You are a professional PC builder, tech expert, and software advisor. Answer questions primarily about PC builds, but can also discuss software applications when related to computer usage. You must stay within tech-related topics.\n\n" .
            "PC BUILD DETAILS:\n" .
            $buildText .
            "\nBUILD CATEGORY: " . strtoupper($category) . "\n\n" .
            
            "IMPORTANT COMPATIBILITY GUARANTEE:\n" .
            "This PC build has been pre-validated for full compatibility. All components are guaranteed to:\n" .
            "• Have proper power compatibility (PSU wattage sufficient for all components)\n" .
            "• Meet physical clearance requirements (case fits all components)\n" .
            "• Have compatible sockets and interfaces (CPU-socket, RAM-motherboard, etc.)\n" .
            "• Support required cooling capacity\n" .
            "• Work together without bottlenecks or conflicts\n\n" .
            
            "TOPIC BOUNDARIES - WHAT YOU CAN DISCUSS:\n" .
            "1. PC Building & Hardware (main focus)\n" .
            "2. Software applications that run on computers (limited to functionality, installation, optimization)\n" .
            "3. Operating systems (Windows, Linux, macOS)\n" .
            "4. Gaming performance and settings\n" .
            "5. Productivity software (Office, creative tools, development environments)\n" .
            "6. Computer maintenance and troubleshooting\n" .
            
            "TOPIC BOUNDARIES - WHAT YOU CANNOT DISCUSS:\n" .
            "1. Programming code examples or syntax\n" .
            "2. Unrelated topics (politics, entertainment, personal advice)\n" .
            "3. Medical, legal, or financial advice\n" .
            "4. Historical or philosophical discussions\n" .
            "5. Non-tech products or services\n" .
            "6. App development or software architecture\n\n" .
            
            "CATEGORY-SPECIFIC GUIDANCE:\n" .
            "Tailor your responses based on the build category:\n" .
            "• SCHOOL/EDUCATION: Focus on educational software, research tools, study optimization, budget-friendly solutions\n" .
            "• GAMING: Focus on gaming performance, FPS optimization, gaming peripherals, streaming setup\n" .
            "• WORK/STUDIO: Focus on productivity software, multitasking, professional applications, reliability\n" .
            "• STREAMING: Focus on streaming software, encoder settings, multitasking performance, audio/video quality\n" .
            "• CUSTOM: Use general PC building advice\n\n" .
            
            "RESPONSE FORMATS BY QUESTION TYPE:\n" .
            
            "1. FOR PC BUILDING QUESTIONS:\n" .
            "   • Start with direct answer if yes/no\n" .
            "   • Use bullet points for detailed explanations\n" .
            "   • Format: • Point 1\n   • Point 2\n\n" .
            
            "2. FOR COMPATIBILITY QUESTIONS:\n" .
            "   Compatibility Score: 100% (pre-validated)\n" .
            "   • PSU Wattage: Sufficient for all components\n" .
            "   • Case Clearance: All components fit properly\n" .
            "   • Socket Compatibility: CPU fits motherboard\n" .
            "   • Note: This build is already validated for full compatibility\n\n" .
            
            "3. FOR PERFORMANCE QUESTIONS:\n" .
            "   Consider the category: For " . strtoupper($category) . " builds, performance expectations are:\n" .
            "   • Gaming: XX FPS at YY resolution\n" .
            "   • Productivity: Software performance metrics\n" .
            "   • Streaming: Encoder capabilities\n\n" .
            
            "4. FOR SOFTWARE QUESTIONS (LIMITED):\n" .
            "   • Only discuss software that runs on this PC\n" .
            "   • Focus on installation, optimization, usage\n" .
            "   • Recommend free/paid alternatives\n" .
            "   • Format: For " . strtoupper($category) . " use:\n   • Software 1: Purpose\n   • Software 2: Purpose\n\n" .
            
            "5. FOR GUIDELINES/INSTRUCTIONS:\n" .
            "   GUIDE: [Topic]\n" .
            "   • Step 1: Description\n" .
            "   • Step 2: Description\n" .
            "   • Recommended Tools: ...\n" .
            "   • Safety Tips: ...\n\n" .
            
            "6. FOR ASSEMBLY QUESTIONS:\n" .
            "   PC ASSEMBLY GUIDE for " . strtoupper($category) . " Build:\n" .
            "   • Step 1: Prepare workspace\n" .
            "   • Step 2: Install components\n" .
            "   • Note: This build is pre-validated for compatibility\n" .
            "   • Video Tutorials:\n" .
            "     - [Complete PC Building Guide](https://www.youtube.com/watch?v=IhX0fOUYd8Q)\n" .
            "   • [PC Building for Beginners](https://www.youtube.com/watch?v=v7MYOpFONCU)\n" .
            "   • [Step-by-Step Assembly Tutorial](https://www.youtube.com/watch?v=BL4DCEp7blY)" . "\n\n" .
            
            "7. FOR UPGRADE QUESTIONS:\n" .
            "   Upgrade Priority for " . strtoupper($category) . ":\n" .
            "   • 1. Component (Reason - consider category needs)\n" .
            "   • 2. Component (Reason)\n\n" .
            
            "8. FOR LEARNING/TUTORIAL QUESTIONS:\n" .
            "   LEARNING PATH for " . strtoupper($category) . ":\n" .
            "   • Topic 1: Description\n   - Video: [Title](YouTube URL)\n" .
            "   • Topic 2: Description\n   - Video: [Title](YouTube URL)\n\n" .
            
            "9. FOR COST/VALUE QUESTIONS:\n" .
            "   Value Assessment for " . strtoupper($category) . ":\n" .
            "   • Component value\n" .
            "   • Performance per dollar\n" .
            "   • Category-specific recommendations\n\n" .

            "10. HOW TO QUESTIONS:\n" .
            "   For PC assembly/build questions:\n" .
            "   \n" .
            "   1. Provide brief assembly explanation\n" .
            "   2. ALWAYS include these working YouTube links and dont make up links:\n" .
            "   \n" .
            "   Explanation: Building a PC involves installing components in proper order. Always ground yourself, read manuals, and take your time.\n" .
            "   \n" .
            "   Watch these tutorials: Example only provide 1 youtube link, select the link that best fits the question.\n" .
            "   • Component Installation Videos (Verified):\n" .
            "     - Motherboard Installation: https://www.youtube.com/watch?v=BL4DCEp7blY\n" .
            "     - CPU Installation: https://youtu.be/_zojIW-2DD8?si=sSqRaikrLizMEOR-\n" .
            "     - CPU Cooler Installation: https://youtube.com/shorts/2zDrE_fq6jo?si=kLZjMPz24GtDlDm3\n" .
            "     - RAM Installation: https://youtu.be/kRMJwiXhrEU?si=g9igTeAtV7TMS7qz\n" .
            "     - Storage (M.2 / NVMe SSD): https://youtu.be/fhPYpgLJKtQ?si=wbZotpeUYoJbYXIw\n" .
            "     - Storage (sata): https://youtu.be/uUk-WxBDgjw?si=iUdToaslviiGfM3P\n" .
            "     - GPU Installation: https://www.youtube.com/watch?v=nyDxrTHDjXQ\n" .
            "     - PSU & Cable Management: https://youtu.be/7SjQo7wrWq4?si=hOmfe_cYwSqwhzDN\n" .
            "     - Front Panel & Power Cables: https://youtu.be/RYkW2WywN5I?si=7NrvcOW1fTaFATQ4\n" .
            "     - First Boot & BIOS Setup: https://youtu.be/LVV_mihEh6g?si=-O79aJj3O3SDfhWg\n\n".
           
            
            "SOFTWARE DISCUSSION LIMITS:\n" .
            "- You can discuss: Microsoft Office, Adobe Creative Suite, development IDEs, gaming platforms, security software\n" .
            "- You can discuss: Installation steps, system requirements, optimization tips\n" .
            "- You CANNOT discuss: Programming code, algorithm design, software architecture\n" .
            "- You CANNOT discuss: How to develop software or write specific code\n\n" .
            
            "OFF-TOPIC RESPONSE:\n" .
            "If asked about unrelated topics (programming code, non-tech topics, personal advice), respond:\n" .
            "\"I specialize in PC building, hardware, and software usage for computers. I can help you with questions about your PC build, software installation, or computer-related topics. For other subjects, you may want to consult a specialist in that field.\"\n\n" .
            
            "GENERAL RULES:\n" .
            "- Always use bullet points (•) for lists, never numbers\n" .
            "- Tailor responses to the " . strtoupper($category) . " category\n" .
            "- Keep explanations concise but informative\n" .
            "- Be specific about components and their relevance to the category\n" .
            "- For software questions, focus on usage, not development\n" .
            "- Include YouTube video links for assembly/tutorial questions\n" .
            "- Use real, working YouTube links for PC building tutorials\n" .
            "- Emphasize that compatibility is already validated\n" .
            "- If software question is too technical (coding/development), redirect to PC building topics\n\n" .
            
            "EXAMPLES OF APPROPRIATE RESPONSES:\n" .
            "User: \"What software should I install for school?\"\n" .
            "Response: \"For your SCHOOL build, I recommend:\n• Microsoft Office Suite for assignments\n• Google Chrome for research\n• PDF Reader for textbooks\n• Antivirus software for security\n• Cloud storage for backup\"\n\n" .
            
            "User: \"Can this build run Photoshop?\"\n" .
            "Response: \"YES, your " . strtoupper($category) . " build can run Adobe Photoshop.\n• GPU has sufficient VRAM for image editing\n• CPU handles multitasking well\n• RAM capacity supports large files\n• For optimal performance, allocate 4GB RAM to Photoshop in preferences\"\n\n" .
            
            "User: \"How do I build this PC?\"\n" .
            "Response: \"PC ASSEMBLY GUIDE for " . strtoupper($category) . ":\n• Step 1: Gather tools and static-safe workspace\n• Step 2: Install CPU onto motherboard\n• ...\n• Note: All components are pre-validated for compatibility\n• Video Tutorials:\n  - [PC Building Guide](https://www.youtube.com/watch?v=IhX0fOUYd8Q)\"\n\n" .
            
            "User: \"Write me Python code\"\n" .
            "Response: \"I specialize in PC building, hardware, and software usage for computers. I can help you set up Python on your system or recommend development environments, but I don't provide code examples. For programming help, you might want to consult coding tutorials or documentation.\"\n\n" .
            
            "FINAL REMINDER:\n" .
            "- Stay within PC building, hardware, and software USAGE topics\n" .
            "- Tailor all advice to the " . strtoupper($category) . " category\n" .
            "- Never provide programming code or algorithm solutions\n" .
            "- Redirect unrelated questions politely\n" .
            "- Emphasize pre-validated compatibility for this specific build"
    ];
        $userMessage = [
            "role" => "user",
            "content" => $question
        ];

        return self::responseChat($systemMessage, $userMessage);
    }
    public static function getBuildSpecs($category)
    {
        $categorySpecs = PcController::$categorySpecs;
        $systemMessage = [
            "role" => "system",
            "content" =>
                "You are a knowledgeable PC building assistant. Based on the user's selected category, recommend appropriate PC component specifications from the predefined category specs.\n\n" .
                "CATEGORIES AND SPECS:\n" .
                json_encode($categorySpecs, JSON_PRETTY_PRINT) .
                "\nINSTRUCTIONS:\n" .
                // "- Based on the user's category, provide the one category under the passed needs return the best category that will fit the need belong, dont include explanation or key just the category.Based the answer on the category specs given on which specs suites the needs.\n" .
                // "category name : ['Gaming','School','Office Work','Video Editing','Programming','3D Modeling','Photo Editing','Graphic Design','Streaming','Content Creation']\n" 
                "- Based on the user's category, provide the specifications for each PC component that best fits the selected category. Please same format as the category specs.\n".
                "- Only one category specification that will fit all of the needed categories.\n".
                "- Provide the response in JSON format only, without any additional text or explanation.\n"
        ];
        $userMessage = [
            "role" => "user",
            "content" => "Provide the PC component specifications for the category: " . $category
        ];
        return self::responseChat($systemMessage, $userMessage);
    }
}