<?php

namespace App\Http\Controllers;
use App\Http\Services\AiService;
use App\Models\Cpu;
use App\Models\CpuCooler;
use App\Models\Motherboard;
use App\Models\PcCase;
use App\Models\Psu;
use App\Models\Ram;
use App\Models\Gpu;
use App\Models\Storage;
use Illuminate\Http\Request;

class MainController extends Controller
{
    protected static $allParts = [];
    protected static $budgetDistribution = [];

    // Static minimum specs for categories
    protected function getCategorySpecs($category)
    {
        $categorySpecs = [
            'Gaming' => [
                'cpu' => ['socket' => 'AM5', 'core_count_min' => 6, 'boost_clock_min_ghz' => 3.5],
                'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
                'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 16, 'min_speed' => 5200],
                'gpu' => ['max_length_mm' => 320, 'recommended_vram_gb' => 8],
                'gpu_required' => true,
                'storage' => ['is_nvme' => true, 'capacity_min_gb' => 1000, 'nvme_required' => true],
                'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 160],
                'psu' => ['wattage_min' => 650],
                'pc_case' => ['motherboard_form_factor' => 'ATX', 'gpu_max_length_mm' => 350]
            ],
            'School' => [
                'cpu' => ['socket' => 'AM5', 'core_count_min' => 4, 'boost_clock_min_ghz' => 3.0],
                'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 2],
                'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 8, 'min_speed' => 4800],
                'gpu' => ['max_length_mm' => 250, 'recommended_vram_gb' => 4],
                'gpu_required' => false,
                'storage' => ['is_nvme' => false, 'capacity_min_gb' => 500, 'nvme_required' => false],
                'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 140],
                'psu' => ['wattage_min' => 500],
                'pc_case' => ['motherboard_form_factor' => 'ATX', 'gpu_max_length_mm' => 270]
            ],
            'Office Work' => [
                'cpu' => ['socket' => 'AM5', 'core_count_min' => 4, 'boost_clock_min_ghz' => 3.0],
                'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 2],
                'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 8, 'min_speed' => 4800],
                'gpu' => ['max_length_mm' => 250, 'recommended_vram_gb' => 2],
                'gpu_required' => false,
                'storage' => ['is_nvme' => false, 'capacity_min_gb' => 500, 'nvme_required' => false],
                'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 140],
                'psu' => ['wattage_min' => 500],
                'pc_case' => ['motherboard_form_factor' => 'ATX', 'gpu_max_length_mm' => 270]
            ],
            'Video Editing' => [
                'cpu' => ['socket' => 'AM5', 'core_count_min' => 8, 'boost_clock_min_ghz' => 3.7],
                'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
                'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 32, 'min_speed' => 5200],
                'gpu' => ['max_length_mm' => 320, 'recommended_vram_gb' => 8],
                'gpu_required' => true,
                'storage' => ['is_nvme' => true, 'capacity_min_gb' => 2000, 'nvme_required' => true],
                'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 160],
                'psu' => ['wattage_min' => 750],
                'pc_case' => ['motherboard_form_factor' => 'ATX', 'gpu_max_length_mm' => 350]
            ],
            'Programming' => [
                'cpu' => ['socket' => 'AM5', 'core_count_min' => 4, 'boost_clock_min_ghz' => 3.2],
                'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 2],
                'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 16, 'min_speed' => 4800],
                'gpu' => ['max_length_mm' => 250, 'recommended_vram_gb' => 4],
                'gpu_required' => false,
                'storage' => ['is_nvme' => false, 'capacity_min_gb' => 500, 'nvme_required' => false],
                'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 140],
                'psu' => ['wattage_min' => 500],
                'pc_case' => ['motherboard_form_factor' => 'ATX', 'gpu_max_length_mm' => 270]
            ],
            '3D Modeling' => [
                'cpu' => ['socket' => 'AM5', 'core_count_min' => 12, 'boost_clock_min_ghz' => 3.8],
                'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
                'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 64, 'min_speed' => 5200],
                'gpu' => ['max_length_mm' => 320, 'recommended_vram_gb' => 12],
                'gpu_required' => true,
                'storage' => ['is_nvme' => true, 'capacity_min_gb' => 2000, 'nvme_required' => true],
                'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 170],
                'psu' => ['wattage_min' => 850],
                'pc_case' => ['motherboard_form_factor' => 'ATX', 'gpu_max_length_mm' => 350]
            ],
                        'Photo Editing' => [
                'cpu' => ['socket' => 'AM5', 'core_count_min' => 6, 'boost_clock_min_ghz' => 3.5],
                'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
                'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 16, 'min_speed' => 5200],
                'gpu' => ['max_length_mm' => 280, 'recommended_vram_gb' => 6],
                'gpu_required' => true,
                'storage' => ['is_nvme' => true, 'capacity_min_gb' => 1000, 'nvme_required' => true],
                'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 150],
                'psu' => ['wattage_min' => 650],
                'pc_case' => ['motherboard_form_factor' => 'ATX', 'gpu_max_length_mm' => 300]
            ],
            'Graphic Design' => [
                'cpu' => ['socket' => 'AM5', 'core_count_min' => 6, 'boost_clock_min_ghz' => 3.5],
                'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
                'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 16, 'min_speed' => 5200],
                'gpu' => ['max_length_mm' => 280, 'recommended_vram_gb' => 6],
                'gpu_required' => true,
                'storage' => ['is_nvme' => true, 'capacity_min_gb' => 1000, 'nvme_required' => true],
                'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 150],
                'psu' => ['wattage_min' => 650],
                'pc_case' => ['motherboard_form_factor' => 'ATX', 'gpu_max_length_mm' => 300]
            ],
            'Streaming' => [
                'cpu' => ['socket' => 'AM5', 'core_count_min' => 8, 'boost_clock_min_ghz' => 3.7],
                'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
                'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 32, 'min_speed' => 5200],
                'gpu' => ['max_length_mm' => 320, 'recommended_vram_gb' => 8],
                'gpu_required' => true,
                'storage' => ['is_nvme' => true, 'capacity_min_gb' => 2000, 'nvme_required' => true],
                'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 160],
                'psu' => ['wattage_min' => 750],
                'pc_case' => ['motherboard_form_factor' => 'ATX', 'gpu_max_length_mm' => 350]
            ],
            'Content Creation' => [
                'cpu' => ['socket' => 'AM5', 'core_count_min' => 8, 'boost_clock_min_ghz' => 3.7],
                'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
                'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 32, 'min_speed' => 5200],
                'gpu' => ['max_length_mm' => 320, 'recommended_vram_gb' => 8],
                'gpu_required' => true,
                'storage' => ['is_nvme' => true, 'capacity_min_gb' => 2000, 'nvme_required' => true],
                'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 160],
                'psu' => ['wattage_min' => 750],
                'pc_case' => ['motherboard_form_factor' => 'ATX', 'gpu_max_length_mm' => 350]
            ],
        ];

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

        // CPU
        $allCpu = Cpu::where('socket', $minimumReq['cpu']['socket'])
            ->where('core_count', '>=', $minimumReq['cpu']['core_count_min'])
            ->where('boost_clock', '>=', $minimumReq['cpu']['boost_clock_min_ghz'])
            ->where('integrated_graphics', '!=', 'None')
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

        // GPU (conditionally)
        $allGpu = collect();
        if (!empty($minimumReq['gpu_required']) && $minimumReq['gpu_required'] === true) {
            $allGpu = Gpu::where('memory', '>=', $minimumReq['gpu']['recommended_vram_gb'])
                ->where('length', '<=', $minimumReq['gpu']['max_length_mm'])
                ->get();
        }

        // CPU Cooler
        $allCooler = CpuCooler::whereRaw("POSITION(? IN cpu_socket) > 0", [$minimumReq['cpu_cooler']['supported_sockets']])
            ->where('height', '<=', $minimumReq['cpu_cooler']['max_height_mm'])
            ->get();

        // Storage
        $allStorage = Storage::whereRaw("CAST(capacity_gb AS INTEGER) >= ?", [$minimumReq['storage']['capacity_min_gb']])
            ->where('is_nvme', $minimumReq['storage']['is_nvme'])
            ->get();

        // PSU
        $allPsu = Psu::where('wattage', '>=', $minimumReq['psu']['wattage_min'])->get();

        // PC Case
        $allCase = PcCase::whereRaw("POSITION(? IN motherboard_form_factor) > 0", [$minimumReq['pc_case']['motherboard_form_factor']])
            ->where('maximum_video_card_length_mm', '>=', $minimumReq['gpu']['max_length_mm'])
            ->get();

        // Return all parts
        self::$allParts = [
            'cpu' => $allCpu,
            'motherboard' => $allMotherboard,
            'ram' => $allRam,
            'gpu' => $allGpu,
            'cpu_cooler' => $allCooler,
            'storage' => $allStorage,
            'psu' => $allPsu,
            'pc_case' => $allCase,
        ];

        return self::$allParts;
    }

    public function buildWithBudgetRange(Request $request)
    {
        $category = $request->input('category');
        $minBudget = (float) $request->input('min', 1000);
        $maxBudget = (float) $request->input('max', 10000);
        $targetBuildCount = 20;

        $allParts = $this->getCompatibleParts($category);
        $convert = fn($p) => $this->convertPrice($p->price);

        // Calculate minimum possible build
        $minimumBuild = collect($allParts)->map(function ($parts) use ($convert) {
            return $parts->isEmpty() ? 0 : $convert($parts->sortBy(fn($p) => $convert($p))->first());
        })->sum();

        if ($maxBudget < $minimumBuild) {
            return response()->json([
                'error' => 'Your budget is less than the minimum possible build for this category.',
                'minimum_possible_build' => round($minimumBuild, 2)
            ], 400);
        }

        if ($minBudget < $minimumBuild) {
            $minBudget = $minimumBuild;
        }

        $builds = [];
        $tiers = ['min', 'mid', 'max'];
        $minimumReq = $this->getCategorySpecs($category);
        $gpuRequired = $minimumReq['gpu_required'] ?? false;

        $attempts = 0;
        while (count($builds) < $targetBuildCount && $attempts < 5000) {
            $attempts++;
            $build = [];
            $total = 0;

            $tier = $tiers[array_rand($tiers)];

            foreach ($allParts as $cat => $parts) {
                if ($parts->isEmpty()) continue;
                if ($cat === 'gpu' && !$gpuRequired) continue; // skip GPU if not required

                $sorted = $parts->sortBy(fn($p) => $convert($p))->values();
                $count = $sorted->count();

                if ($tier === 'min') {
                    $index = rand(0, max(0, (int) floor($count * 0.3) - 1));
                } elseif ($tier === 'max') {
                    $index = rand((int) floor($count * 0.7), $count - 1);
                } else {
                    $index = rand((int) floor($count * 0.3), (int) floor($count * 0.7));
                }

                $selectedPart = $sorted[$index];
                $build[$cat] = $selectedPart;
                $total += $convert($selectedPart);
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
                            'product' => $p->product_url ?? ''
                        ];
                    })->values(),
                    'total_price' => round($total, 2)
                ];
            }
        }

        if (empty($builds)) {
            $minCombo = [];
            foreach ($allParts as $cat => $parts) {
                if ($parts->isEmpty()) continue;
                if ($cat === 'gpu' && !$gpuRequired) continue;
                $minCombo[$cat] = $parts->sortBy(fn($p) => $convert($p))->first();
            }
            $total = array_reduce($minCombo, fn($sum, $p) => $sum + $convert($p), 0);
            $builds[] = [
                'parts' => collect($minCombo)->map(function ($p, $cat) use ($convert) {
                    return [
                        'id' => $p->id,
                        'partType' => ucwords(str_replace('_', ' ', $cat)),
                        'name' => $p->name,
                        'price' => $convert($p),
                        'image' => $p->image ?? ''
                    ];
                })->values(),
                'total_price' => round($total, 2)
            ];
        }

        usort($builds, fn($a, $b) => $a['total_price'] <=> $b['total_price']);

        return response()->json([
            'total_builds' => count($builds),
            'builds' => $builds,
            'minimum_possible_build' => round($minimumBuild, 2),
            'sorted_by' => 'randomized_tier_based'
        ]);
    }
    public function AiChatbot(Request $request)
    {
        $build = $request->input('build');
        $question = $request->input('question');
        $result = AiService::askAI($build, $question);
        $clean = preg_replace('/```(json)?|```/', '', $result);
        $response = json_decode($clean, true);
        return response()->json(['message' => $response]);
    }
}
