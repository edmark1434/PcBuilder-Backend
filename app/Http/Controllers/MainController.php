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
use Illuminate\Http\Request;
use App\Http\Controllers\PcController;
class MainController extends Controller
{
    protected static $allParts = [];
    protected static $budgetDistribution = [];

    // Static minimum specs for categories
    protected function getCategorySpecs($category)
    {
        $categorySpecs = PcController::$categorySpecs;
        if(in_array($category, array_keys($categorySpecs)) === false) {
            $result = AiService::getBuildSpecs($category);
            $claenResult = preg_replace('/```(json)?|```/', '', $result);
            $claenResult = trim($claenResult);
            $decoded = json_decode($claenResult, true);
            return $decoded;
        }
        return $categorySpecs[$category] ?? null;
    }

    protected function convertPrice($priceText)
    {
        return round((float) str_replace(',', '', preg_replace('/[^0-9.]/', '', $priceText)), 2);
    }

    public function getCompatibleParts($category)
    {
        $minimumReq = $this->getCategorySpecs($category);
        if (!$minimumReq) return [];

        $gpuRequired = $minimumReq['gpu_required'];

        // CPU
        $allCpu = Cpu::where('socket', $minimumReq['cpu']['socket'])
            ->where('core_count', '>=', $minimumReq['cpu']['core_count_min'])
            ->where('boost_clock', '>=', $minimumReq['cpu']['boost_clock_min_ghz'])
            ->get();

        // Motherboard
        $allMotherboard = Motherboard::where('socket_cpu', $minimumReq['motherboard']['socket_cpu'])
            ->where('form_factor', $minimumReq['motherboard']['form_factor'])
            ->where('memory_type', $minimumReq['motherboard']['memory_type'])
            ->where('memory_slots', '>=', $minimumReq['motherboard']['memory_slots'])
            ->get();

        // RAM
        $allRam = Ram::where('form_factor', 'ILIKE', '%' . $minimumReq['ram']['type'] . '%')
            ->whereRaw("
                (CAST(SPLIT_PART(modules, ' x ', 1) AS INTEGER) *
                CAST(REGEXP_REPLACE(SPLIT_PART(modules, ' x ', 2), 'GB', '', 'g') AS INTEGER)
                ) >= ?
            ", [$minimumReq['ram']['capacity_min_gb']])
            ->whereRaw("CAST(SPLIT_PART(speed, '-', 2) AS INTEGER) >= ?", [$minimumReq['ram']['min_speed']])
            ->get();

        // GPU (only if required)
        $allGpu = collect();
        if ($gpuRequired) {
            $allGpu = Gpu::where('memory', '>=', $minimumReq['gpu']['recommended_vram_gb'])
                ->where('length', '<=', $minimumReq['gpu']['max_length_mm'])
                ->get();
        }

        // CPU Cooler
        $minRpm = $minimumReq['cpu_cooler']['fan_rpm_min'];
        $maxRpm = $minimumReq['cpu_cooler']['fan_rpm_max'];

        $allCooler = CpuCooler::whereRaw(
            "POSITION(? IN cpu_socket) > 0",
            [$minimumReq['cpu_cooler']['supported_sockets']]
            )
            ->where('height', '<=', $minimumReq['cpu_cooler']['max_height_mm'])
            ->whereRaw("
                (
                    -- CASE 1: RANGE RPM (e.g. 600 - 1500 RPM)
                    (
                        fan_rpm LIKE '%-%' AND
                        SPLIT_PART(fan_rpm, ' ', 1) ~ '^[0-9]+$' AND
                        SPLIT_PART(SPLIT_PART(fan_rpm, '-', 2), ' ', 1) ~ '^[0-9]+$' AND
                        CAST(SPLIT_PART(fan_rpm, ' ', 1) AS INTEGER) <= ? AND
                        CAST(SPLIT_PART(SPLIT_PART(fan_rpm, '-', 2), ' ', 1) AS INTEGER) >= ?
                    )
                    OR
                    -- CASE 2: SINGLE RPM (e.g. 2000 RPM)
                    (
                        fan_rpm NOT LIKE '%-%' AND
                        SPLIT_PART(fan_rpm, ' ', 1) ~ '^[0-9]+$' AND
                        CAST(SPLIT_PART(fan_rpm, ' ', 1) AS INTEGER) BETWEEN ? AND ?
                    )
                )
            ", [$maxRpm, $minRpm, $minRpm, $maxRpm])
            ->get();

        // Storage
        $allStorage = Storage::whereRaw("CAST(capacity_gb AS INTEGER) >= ?", [$minimumReq['storage']['capacity_min_gb']])
            ->where('is_nvme', $minimumReq['storage']['is_nvme'])
            ->where('type', $minimumReq['storage']['type'])
            ->get();

        // PSU
        $allPsu = Psu::where('wattage', '>=', $minimumReq['psu']['wattage_min'])->get();

        // PC Case (skip GPU length if GPU is optional)
        $pcCaseQuery = PcCase::whereRaw(
            "POSITION(? IN LOWER(motherboard_form_factor)) > 0",
            [strtolower($minimumReq['pc_case']['motherboard_form_factor'])]
        );

        if ($gpuRequired) {
            $pcCaseQuery->where('maximum_video_card_length_mm', '>=', $minimumReq['gpu']['max_length_mm']);
        }

        $allCase = $pcCaseQuery->get();

        // Final parts list
        self::$allParts = [
            'cpu' => $allCpu,
            'motherboard' => $allMotherboard,
            'ram' => $allRam,
            'cpu_cooler' => $allCooler,
            'storage' => $allStorage,
            'psu' => $allPsu,
            'pc_case' => $allCase,
        ];

        if ($gpuRequired) {
            self::$allParts['gpu'] = $allGpu;
        }

        return self::$allParts;
    }


    public function buildWithBudgetRange(Request $request)
    {
        $category = $request->input('category');
  
        $targetBuildCount = 20;

        $categorySpecs = $this->getCategorySpecs($category);
        $gpuRequired = $categorySpecs['gpu_required'] ?? true;

        // Get all parts
        $allParts = $this->getCompatibleParts($category);

        // Remove GPU from allParts if category does not require it
        if (!$gpuRequired) {
            unset($allParts['gpu']);
        }

        $convert = fn($p) => $this->convertPrice($p->price);

        // Minimum possible build
        $minimumBuild = collect($allParts)->map(function ($parts) use ($convert) {
            return $parts->isEmpty() ? 0 : $convert($parts->sortBy(fn($p) => $convert($p))->first());
        })->sum();

        $minBudget = (float) $request->input('min', $minimumBuild);
        $maxBudget = (float) $request->input('max', 10000);

        if ($maxBudget < $minimumBuild) {
            return response()->json([
                'error' => 'Your budget is less than the minimum build.',
                'minimum_possible_build' => round($minimumBuild, 2)
            ], 400);
        }

        if ($minBudget < $minimumBuild) {
            $minBudget = $minimumBuild;
        }

        // If budget is too close: return minimum build
        if (($maxBudget - $minimumBuild) <= 20) {
            return response()->json([
                'total_builds' => 1,
                'builds' => [$this->formatMinimumBuild($allParts, $convert)],
                'minimum_possible_build' => round($minimumBuild, 2),
                'sorted_by' => 'minimum_build_short_range'
            ]);
        }

        // Generate builds
        $builds = [];
        $tiers = ['min', 'mid', 'max'];
        $attemptLimit = 1500;
        $attempts = 0;

        while (count($builds) < $targetBuildCount && $attempts < $attemptLimit) {
            $attempts++;

            $build = [];
            $total = 0;
            $tier = $tiers[array_rand($tiers)];

            foreach ($allParts as $cat => $parts) {
                if ($parts->isEmpty()) continue;

                $sorted = $parts->sortBy(fn($p) => $convert($p))->values();
                $count = $sorted->count();

                if ($count === 0) continue;

                if ($tier === 'min') {
                    $index = rand(0, max(0, (int) floor($count * 0.3) - 1));
                } elseif ($tier === 'max') {
                    $index = rand((int) floor($count * 0.7), $count - 1);
                } else {
                    $index = rand((int) floor($count * 0.3), (int) floor($count * 0.7));
                }

                $selected = $sorted[$index];
                $build[$cat] = $selected;
                $total += $convert($selected);
            }

            if ($total >= $minBudget && $total <= $maxBudget) {
                $builds[] = [
                    'parts' => collect($build)->map(function ($p, $cat) use ($convert) {
                        return [
                            'id' => $p->id,
                            'partType' => ucwords(str_replace('_', ' ', $cat)),
                            'name' => $p->name,
                            'price' => $convert($p),
                            'image' => $p->image_url ?? '',
                            'product' => $p->product_url ??
 ''
                        ];
                    })->values(),
                    'total_price' => round($total, 2)
                ];
            }
        }

        // Fallback: minimum build
        if (empty($builds)) {
            return response()->json([
                'total_builds' => 1,
                'builds' => [$this->formatMinimumBuild($allParts, $convert)],
                'minimum_possible_build' => round($minimumBuild, 2),
                'sorted_by' => 'fallback_minimum'
            ]);
        }

        // Add minimum build at first index
        array_unshift($builds, $this->formatMinimumBuild($allParts, $convert));

        // Sort ascending by total price
        usort($builds, fn($a, $b) => $a['total_price'] <=> $b['total_price']);

        // Remove duplicates
        $builds = array_unique($builds, SORT_REGULAR);

        // 20 builds limit
        $builds = array_slice($builds, 0, 20);

        return response()->json([
            'total_builds' => count($builds),
            'builds' => $builds,
            'minimum_possible_build' => round($minimumBuild, 2),
            'sorted_by' => 'fast_generation_with_minimum_first'
        ]);
    }

    private function formatMinimumBuild($allParts, $convert)
    {
        $minCombo = [];

        foreach ($allParts as $cat => $parts) {
            if (!$parts->isEmpty()) {
                $minCombo[$cat] = $parts->sortBy(fn($p) => $convert($p))->first();
            }
        }

        $total = array_reduce($minCombo, fn($sum, $p) => $sum + $convert($p), 0);

        return [
            'parts' => collect($minCombo)->map(function ($p, $cat) use ($convert) {
                return [
                    'id' => $p->id,
                    'partType' => ucwords(str_replace('_', ' ', $cat)),
                    'name' => $p->name,
                    'price' => $convert($p),
                    'image' => $p->image_url ?? '',
                    'product' => $p->product_url ?? ''
                ];
            })->values(),
            'total_price' => round($total, 2)
        ];
    }

    // AI Chatbot Proxy
    public function AiChatbot(Request $request)
    {
        try {
            $build = $request->input('build');
            $question = $request->input('question');
            $category = $request->input('category');
            // Validate inputs
            if (!$build || !$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'Build data and question are required'
                ], 400);
            }

            // Call the llms
            $result = AiService::askAI($build, $question,$category);
            
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
