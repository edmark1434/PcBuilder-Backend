<?php

namespace App\Http\Controllers;

use App\Models\Cpu;
use App\Models\CpuCooler;
use App\Models\Motherboard;
use App\Models\PcCase;
use App\Models\Psu;
use App\Models\Ram;
use App\Models\Gpu;
use App\Http\Services\AiService;
use App\Models\Storage;
use App\Models\PcPart;
use Illuminate\Http\Request;
use App\Http\Controllers\PcController;
class MainController extends Controller
{
    protected function convertPrice($priceText)
    {
        // Remove all non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', $priceText);
        return round((float) $cleaned, 2);
    }

    
    public function buildWithBudgetRange(Request $request)
    {
        $text = $request->input('text', '');
        $description = $request->input('description');

        if (empty($text)) {
            $text = $description;
        }

        if (!$description) {
            return response()->json([
                'error' => 'Missing required parameter: description',
            ], 400);
        }

        $minBudget = (float) $request->input('min', 0);
        $maxBudget = (float) $request->input('max', 0);
        $detailedNeeds = $request->input('detailed_needs', '');

        try {
            $components = $this->loadAndGroupComponents();
            $componentsSummary = $this->createComponentsSummary($components);

            $aiResponse = AiService::generateBuildRecommendation(
                $text,
                $description,
                $detailedNeeds,
                $minBudget,
                $maxBudget,
                $componentsSummary
            );

            // If AI returns an array already, use it
            if (is_array($aiResponse)) {
                $decoded = $aiResponse;
            } else {
                // Remove Markdown/code blocks
                $aiResponseClean = preg_replace('/```(json)?|```/', '', $aiResponse);
                $aiResponseClean = trim($aiResponseClean);

                // Attempt to decode JSON safely
                $decoded = json_decode($aiResponseClean, true);

                // Fallback: try to extract JSON object from text if partial
                if (json_last_error() !== JSON_ERROR_NONE) {
                    preg_match('/\{.*\}/s', $aiResponseClean, $matches);
                    if (!empty($matches)) {
                        $decoded = json_decode($matches[0], true);
                    }
                }
            }

            // Ensure we always return an array of builds
            if (!isset($decoded['builds']) || !is_array($decoded['builds'])) {
                $decoded['builds'] = $this->buildFromRecommendation($components, is_array($aiResponse) ? json_encode($aiResponse) : $aiResponse);
            }


            $response = [
                'success' => true,
                'data' => [
                    'builds' => $decoded['builds']
                ]
            ];

            if ($maxBudget > 0) {
                $response['data']['budget_range'] = [
                    'min' => $minBudget,
                    'max' => $maxBudget
                ];
            }

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('buildWithBudgetRange Error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate build recommendation',
                'message' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function loadAndGroupComponents()
    {
        $components = [
            'Processors' => [],
            'Motherboards' => [],
            'Memory' => [],
            'Storage' => [],
            'Graphics Cards' => [],
            'Power Supply' => [],
            'Cases' => [],
            'Cooling' => []
        ];

        // Query database using Laravel ORM
        $parts = PcPart::all();

        foreach ($parts as $part) {
            if (isset($components[$part->type])) {
                $components[$part->type][] = [
                    'ID' => $part->external_id,
                    'Type' => $part->type,
                    'Vendor' => $part->vendor,
                    'Title' => $part->title,
                    'Price' => $part->price,
                    'Image' => $part->image,
                    'Link' => $part->link
                ];
            }
        }

        return $components;
    }

    private function createComponentsSummary($components)
    {
        $summary = "Available Components (ID | Vendor | Title | Price):\n\n";

        foreach ($components as $type => $items) {
            $summary .= strtoupper($type) . " (" . count($items) . " options):\n";

            foreach ($items as $item) {
                $summary .= "{$item['ID']} | {$item['Type']} | {$item['Vendor']} | {$item['Title']} |  {$item['Price']} | {$item['Image']}   | {$item['Link']}\n";
            }
            $summary .= "\n";
        }

        return $summary;
    }

    private function generateSampleBuilds($components, $minBudget, $maxBudget, $recommendation)
    {
        $builds = [];
        
        // Parse recommendation to extract component names and prices
        $recommendedComponents = $this->parseRecommendationComponents($recommendation, $components);

        $parts = [];
        $totalPrice = 0;

        // Build parts array from parsed recommendations or use fallback
        foreach ($components as $type => $items) {
            if (!empty($items)) {
                // Check if we have a recommended component for this type
                $selectedItem = null;
                
                if (isset($recommendedComponents[$type])) {
                    // Try to find the recommended component in the list
                    $recommendedName = $recommendedComponents[$type]['name'];
                    foreach ($items as $item) {
                        if (stripos($item['Title'], $recommendedName) !== false) {
                            $selectedItem = $item;
                            break;
                        }
                    }
                }
                
                // Fallback to first item if not found
                if (!$selectedItem) {
                    $selectedItem = $items[0];
                }
                
                $price = is_numeric($selectedItem['Price']) ? (float)$selectedItem['Price'] : $this->convertPrice($selectedItem['Price']);
                
                $parts[] = [
                    'id' => (int)$selectedItem['ID'],
                    'partType' => $type,
                    'name' => $selectedItem['Title'],
                    'vendor' => $selectedItem['Vendor'],
                    'price' => $price,
                    'image' => $selectedItem['Image'] ?? '',
                    'product' => $selectedItem['Link'] ?? '',
                    'type' => $type,
                    'external_id' => $selectedItem['ID']
                ];
                
                $totalPrice += $price;
            }
        }

        // Create the build
        $build = [
            'parts' => $parts,
            'total_price' => $totalPrice,
            'description' => $recommendation
        ];

        $builds[] = $build;

        return $builds;
    }

    private function buildFromRecommendation($components, $recommendation)
    {
        $parts = [];
        $totalPrice = 0.0;

        // Regex 1: capture lines with explicit IDs
        // Example: • CPU + GPU: **AMD Ryzen 3 3200G** (ID 31) – ₱3,700
        $patternWithId = '/^\s*(?:•|\-|\*)\s*(?:[^:]+:)?\s*\*\*(.*?)\*\*\s*\(ID\s*(\d+)\)\s*[—\-:]+\s*₱?([\d,\.]+)/mi';
        if (preg_match_all($patternWithId, $recommendation, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $name = trim($m[1]);
                $extId = trim($m[2]);
                $priceText = trim($m[3]);
                $price = $this->convertPrice($priceText);

                $matchedItem = null;
                $matchedType = null;

                // Find in components by external_id first
                foreach ($components as $type => $items) {
                    foreach ($items as $item) {
                        if ((string)$item['ID'] === (string)$extId) {
                            $matchedItem = $item;
                            $matchedType = $type;
                            break 2;
                        }
                    }
                }

                // Fallback: fuzzy match by title if ID not found
                if (!$matchedItem) {
                    foreach ($components as $type => $items) {
                        foreach ($items as $item) {
                            if (stripos($item['Title'], $name) !== false) {
                                $matchedItem = $item;
                                $matchedType = $type;
                                break 2;
                            }
                        }
                    }
                }

                // If still not found, create a minimal entry from text
                if (!$matchedItem) {
                    $parts[] = [
                        'ID' => (int)$extId,
                        'Type' => 'Unknown',
                        'Title' => $name,
                        'Vendor' => '',
                        'Price' => $price,
                        'Image' => '',
                        'Link' => '',
                        'external_id' => $extId,
                    ];
                    $totalPrice += $price;
                    continue;
                }

                // Use matched DB item details, override price with recommendation when provided
                $finalPrice = is_numeric($matchedItem['Price']) ? (float)$matchedItem['Price'] : $this->convertPrice($matchedItem['Price']);
                if ($price > 0) {
                    $finalPrice = $price;
                }

                $parts[] = [
                    'ID' => (int)$matchedItem['ID'],
                    'Type' => $matchedType,
                    'Title' => $matchedItem['Title'],
                    'Vendor' => $matchedItem['Vendor'],
                    'Price' => $finalPrice,
                    'Image' => $matchedItem['Image'] ?? '',
                    'Link' => $matchedItem['Link'] ?? '',
                    'external_id' => $matchedItem['ID'],
                ];
                $totalPrice += $finalPrice;
            }
        }

        // Regex 2: capture lines without IDs
        // Example: - **AMD Ryzen 5 4600G** — ₱6,150
        $patternNoId = '/^\s*(?:•|\-|\*)\s*(?:[^:]+:)?\s*\*\*(.*?)\*\*\s*[—\-:]+\s*₱?([\d,\.]+)/mi';
        if (preg_match_all($patternNoId, $recommendation, $matches2, PREG_SET_ORDER)) {
            foreach ($matches2 as $m2) {
                $name = trim($m2[1]);
                $priceText = trim($m2[2]);
                $price = $this->convertPrice($priceText);

                // Skip if already added by ID (check Title key, not name)
                $alreadyAdded = false;
                foreach ($parts as $p) {
                    if (stripos($p['Title'], $name) !== false) {
                        $alreadyAdded = true;
                        break;
                    }
                }
                if ($alreadyAdded) {
                    continue;
                }

                $matchedItem = null;
                $matchedType = null;

                // Fuzzy match across all components by title
                foreach ($components as $type => $items) {
                    foreach ($items as $item) {
                        if (stripos($item['Title'], $name) !== false) {
                            $matchedItem = $item;
                            $matchedType = $type;
                            break 2;
                        }
                    }
                }

                if ($matchedItem) {
                    $finalPrice = is_numeric($matchedItem['Price']) ? (float)$matchedItem['Price'] : $this->convertPrice($matchedItem['Price']);
                    if ($price > 0) {
                        $finalPrice = $price;
                    }
                    $parts[] = [
                        'ID' => (int)$matchedItem['ID'],
                        'Type' => $matchedType,
                        'Title' => $matchedItem['Title'],
                        'Vendor' => $matchedItem['Vendor'],
                        'Price' => $finalPrice,
                        'Image' => $matchedItem['Image'] ?? '',
                        'Link' => $matchedItem['Link'] ?? '',
                        'external_id' => $matchedItem['ID'],
                    ];
                    $totalPrice += $finalPrice;
                } else {
                    // Minimal entry if no DB match
                    $parts[] = [
                        'ID' => 0,
                        'Type' => 'Unknown',
                        'Title' => $name,
                        'Vendor' => '',
                        'Price' => $price,
                        'Image' => '',
                        'Link' => '',
                        'external_id' => '',
                    ];
                    $totalPrice += $price;
                }
            }
        }

        // Assemble single build
        $build = [
            'parts' => $parts,
            'total_price' => $totalPrice,
            'description' => $recommendation,
        ];

        return [$build];
    }

    private function parseRecommendationComponents($recommendation, $components)
    {
        $recommended = [];
        
        // Extract lines that contain component recommendations (pattern: "Component Name — ₱Price")
        // Also handle pattern: "Component Name — Price"
        if (preg_match_all('/^([^—\n]+)\s*(?:—|-|:)\s*₱?[\d,\.]+\s*$/m', $recommendation, $matches)) {
            foreach ($matches[1] as $componentName) {
                $componentName = trim($componentName);
                
                // Skip non-component lines
                if (strlen($componentName) < 3 || preg_match('/^(TOTAL|Optional|Just|If|Add|Note|Let)/i', $componentName)) {
                    continue;
                }
                
                // Try to match with component types
                $matchedType = $this->matchComponentType($componentName, $components);
                
                if ($matchedType) {
                    $recommended[$matchedType] = ['name' => $componentName];
                }
            }
        }
        
        return $recommended;
    }

    private function matchComponentType($componentName, $components)
    {
        $componentName = strtolower($componentName);
        
        // Define keywords for each component type
        $typeKeywords = [
            'Processors' => ['ryzen', 'core', 'intel', 'amd', 'cpu', 'processor', 'xeon'],
            'Motherboards' => ['prime', 'asus', 'msi', 'gigabyte', 'asrock', 'board', 'motherboard', 'z790', 'b760', 'a520', 'x570'],
            'Memory' => ['ddr', 'ram', 'memory', 'adata', 'corsair', 'kingston', 'crucial', 'gskill', 'storm'],
            'Storage' => ['ssd', 'nvme', 'storage', '250gb', '500gb', '1tb', '2tb', 'wd', 'samsung', 'crucial', 'kingston', 'seagate'],
            'Graphics Cards' => ['gtx', 'rtx', 'geforce', 'radeon', 'gpu', 'vega', 'igpu', 'graphics', 'msi', 'asus', 'gigabyte'],
            'Power Supply' => ['psu', 'power', 'supply', 'watts', 'w ', '500w', '650w', '750w', 'bronze', 'gold', 'platinum'],
            'Cases' => ['case', 'chassis', 'tower', 'atx', 'mini', 'antec', 'corsair', 'nzxt', 'trendsonic', 'cooler master'],
            'Cooling' => ['cooler', 'cooling', 'fan', 'tower', 'liquid', 'air']
        ];
        
        // Check which type matches best
        foreach ($typeKeywords as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($componentName, $keyword) !== false) {
                    return $type;
                }
            }
        }
        
        return null;
    }

    

    
    // AI Chatbot Proxy
    public function AiChatbot(Request $request)
    {
        try {
            $build = $request->input('build');
            $question = $request->input('question');
            $needs = $request->input('needs', '');
            // Validate inputs
            if (!$build || !$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'Build data and question are required'
                ], 400);
            }

            // Call the llms
            $result = AiService::askAI($build, $question, $needs);
            
            // check if the response is json
            $jsonStart = strpos($result, '{');
            $jsonEnd = strrpos($result, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
                // Extracting and decoding json
                $jsonStr = substr($result, $jsonStart, $jsonEnd - $jsonStart + 1);
                
                $jsonStr = preg_replace('/```(json)?|```/', '', $jsonStr);
                $jsonStr = trim($jsonStr);
                
                $response = json_decode($jsonStr, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return response()->json([
                        'success' => true,
                        'message' => $response
                    ], 200);
                }
            }
            
            // format for non json returns
            $hasBullets = false;
            $bulletPatterns = [
                '/^\s*[•\-*]\s+/m',           // •, -, * bullets
                '/^\s*\d+\.\s+/m',            // Numbered bullets
                '/Compatibility Score:/i',    // Compatibility headers
                '/GUIDE:/i',                  // Guide headers
                '/Upgrade Priority:/i',       // Upgrade headers
                '/Value Assessment:/i',       // Value headers
                '/YES\s*\n/i',                // YES followed by newline
                '/NO\s*\n/i',                 // NO followed by newline
            ];
            
            foreach ($bulletPatterns as $pattern) {
                if (preg_match($pattern, $result)) {
                    $hasBullets = true;
                    break;
                }
            }
            
            // Clean the response (remove JSON markers if any)
            $cleanResponse = preg_replace('/```(json)?|```/', '', $result);
            $cleanResponse = trim($cleanResponse);
            
            // Format the response
            if ($hasBullets) {
                // For bulleted responses, return as is
                return response()->json([
                    'success' => true,
                    'message' => [
                        'format' => 'bulleted',
                        'content' => $cleanResponse,
                        'has_bullets' => true
                    ]
                ], 200);
            } else {
                // For regular text responses, try to extract direct answer if present
                $lines = explode("\n", $cleanResponse);
                $directAnswer = '';
                $detailedAnswer = '';
                
                // Look for direct answer patterns
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if (preg_match('/^(YES|NO)\b/i', $trimmed, $matches)) {
                        $directAnswer = strtoupper($matches[1]);
                        $detailedAnswer = substr($trimmed, strlen($matches[1]));
                        $detailedAnswer = trim($detailedAnswer, ': .');
                        break;
                    }
                }
                
                if ($directAnswer) {
                    return response()->json([
                        'success' => true,
                        'message' => [
                            'format' => 'qa',
                            'direct_answer' => $directAnswer,
                            'detailed_answer' => $detailedAnswer ?: $cleanResponse,
                            'has_bullets' => false
                        ]
                    ], 200);
                } else {
                    // Return as regular text
                    return response()->json([
                        'success' => true,
                        'message' => [
                            'format' => 'text',
                            'content' => $cleanResponse,
                            'has_bullets' => false
                        ]
                    ], 200);
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('AI Chatbot Error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}
