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

    protected function convertPrice($priceText)
    {
        return round((float) str_replace(',', '', preg_replace('/[^0-9.]/', '', $priceText)), 2);
    }

    public function buildSpec($text)
    {
        $result = AiService::getSpec($text);
        $clean = preg_replace('/```(json)?|```/', '', $result);
        return json_decode($clean, true);
    }

    public function getCompatibleParts($text = null)
    {
        if (!session()->has('minimum')) {
            $text = $text ?? "Build a pc for programming";
            $pcDetails = $this->buildSpec($text);
            $minimumReq = $pcDetails["minimum_required_specs"];
            $distribution = $pcDetails["budget_distribution"];
            session(['minimum' => $minimumReq]);
            session(['budget_distribution' => $distribution]);
        } else {
            $minimumReq = session('minimum');
        }

        self::$allParts = [
            'cpu' => Cpu::get(),
            'motherboard' => Motherboard::get(),
            'ram' => Ram::get(),
            'gpu' => Gpu::get(),
            'cpu_cooler' => CpuCooler::get(),
            'storage' => Storage::get(),
            'psu' => Psu::get(),
            'pc_case' => PcCase::get(),
        ];

        return self::$allParts;
    }

    public function minimumPrice($text = null)
    {
        $allParts = $this->getCompatibleParts($text);
        $convertPrice = fn($priceText) => $this->convertPrice($priceText);

        $minPrice = collect($allParts)->map(function ($parts) use ($convertPrice) {
            if ($parts->isEmpty()) return 0;
            return $parts->min(fn($part) => $convertPrice($part->price));
        })->sum();

        return round($minPrice, 2);
    }

    // ⭐ FINAL VERSION – FULL BUDGET LOGIC ⭐
    public function buildWithBudgetRange(Request $request)
    {
        $text = $request->input('build', 'Build a pc for programming');
        $minBudget = (float) $request->input('min', 1000);
        $maxBudget = (float) $request->input('max', 10000);
        $targetBuildCount = 20;

        $allParts = $this->getCompatibleParts($text);
        $convert = fn($p) => $this->convertPrice($p);

        // Create balanced allocation
        $distribution = $this->getBudgetDistribution(($minBudget + $maxBudget) / 2);

        $builds = [];

        for ($i = 0; $i < 3000; $i++) {

            $build = [];
            $total = 0;

            foreach ($allParts as $category => $parts) {

                if (!isset($distribution[$category])) continue;

                $targetPrice = $distribution[$category];

                $selectedPart = $this->pickPartBasedOnBudget($parts, $targetPrice, $convert);

                $build[$category] = $selectedPart;
                $total += $convert($selectedPart->price);
            }

            if ($total >= $minBudget && $total <= $maxBudget) {
                $builds[] = [
                    'parts' => collect($build)->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => $convert($p->price)
                    ]),
                    'total_price' => round($total, 2)
                ];
            }

            if (count($builds) >= $targetBuildCount) break;
        }

        if (empty($builds)) {
            return response()->json([
                'error' => "No balanced builds found within your budget.",
                'minimum_possible_build' => $this->minimumPrice($text)
            ], 400);
        }

        usort($builds, fn($a, $b) => $a['total_price'] <=> $b['total_price']);

        return response()->json([
            'total_builds' => count($builds),
            'builds' => array_slice($builds, 0, $targetBuildCount),
            'sorted_by' => 'balanced_cheapest_to_expensive'
        ]);
    }

    protected function pickPartBasedOnBudget($parts, $targetBudget, $convert)
    {
        $filtered = $parts->filter(function ($p) use ($targetBudget, $convert) {
            $price = $convert($p->price);
            return $price <= $targetBudget * 1.3 && $price >= $targetBudget * 0.5;
        });

        // Fallback if none in target range
        if ($filtered->isEmpty()) {
            $filtered = $parts;
        }

        return $filtered->random();
    }

    protected function getBudgetDistribution($budget)
    {

        $budget_distribution = session('budget_distribution',[]);
        return [
            'cpu'         => $budget * ($budget_distribution['cpu_percent'] / 100),
            'gpu'         => $budget * ($budget_distribution['gpu_percent'] / 100),
            'ram'         => $budget * ($budget_distribution['ram_percent']/100),
            'storage'     => $budget * ($budget_distribution['storage_percent'] /100),
            'motherboard' => $budget * ($budget_distribution['motherboard_percent']/ 100),
            'psu'         => $budget * ($budget_distribution['psu_percent'] / 100),
            'pc_case'     => $budget * ($budget_distribution['pc_case_percent'] /100),
            'cpu_cooler'  => $budget * ($budget_distribution['cpu_cooler_percent'] / 100)
        ];
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
