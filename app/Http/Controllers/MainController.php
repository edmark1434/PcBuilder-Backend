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
            session(['minimum' => $minimumReq]);
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

        $allParts = $this->getCompatibleParts($text);
        $convert = fn($p) => $this->convertPrice($p);

        // 1️⃣ Compute MINIMUM POSSIBLE BUILD
        $currentBuild = [];
        $currentTotal = 0;

        foreach ($allParts as $category => $parts) {

            if ($parts->isEmpty()) {
                return response()->json([
                    'error' => "No compatible $category parts found"
                ], 400);
            }

            // cheapest part
            $minPrice = $parts->min(fn($p) => $convert($p->price));
            $minParts = $parts->filter(fn($p) => $convert($p->price) == $minPrice);
            $selected = $minParts->random();

            $currentBuild[$category] = $selected;
            $currentTotal += $minPrice;
        }

        // If minimum build > max budget → impossible
        if ($currentTotal > $maxBudget) {
            return response()->json([
                'error' => "Minimum build ($currentTotal) exceeds your max budget",
            ], 400);
        }

        // 2️⃣ If minimum build fits budget → return it
        if ($currentTotal >= $minBudget && $currentTotal <= $maxBudget) {
            return response()->json([
                'selected_parts' => collect($currentBuild)->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $convert($p->price)
                ]),
                'total_price' => round($currentTotal, 2),
                'type' => 'minimum_fit'
            ]);
        }

        // 3️⃣ Upgrade parts until we reach the budget window
        foreach ($allParts as $category => $parts) {

            $sorted = $parts->sortBy(fn($p) => $convert($p->price));

            foreach ($sorted as $part) {

                $newPrice = $convert($part->price);
                $oldPrice = $convert($currentBuild[$category]->price);

                // Skip if not an upgrade
                if ($newPrice <= $oldPrice) continue;

                $newTotal = $currentTotal - $oldPrice + $newPrice;

                // Stop condition: fits budget window
                if ($newTotal >= $minBudget && $newTotal <= $maxBudget) {
                    $currentBuild[$category] = $part;
                    $currentTotal = $newTotal;

                    return response()->json([
                        'selected_parts' => collect($currentBuild)->map(fn($p) => [
                            'id' => $p->id,
                            'name' => $p->name,
                            'price' => $convert($p->price)
                        ]),
                        'total_price' => round($currentTotal, 2),
                        'type' => "upgraded_to_fit"
                    ]);
                }

                // Accept upgrade if below budget and continue searching
                if ($newTotal < $minBudget) {
                    $currentBuild[$category] = $part;
                    $currentTotal = $newTotal;
                }
            }
        }

        return response()->json([
            'error' => "Could not create a build within your budget range.",
            'minimum_possible_build' => $currentTotal
        ], 400);
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
