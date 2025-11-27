<?php

namespace App\Http\Controllers;
use App\Http\Services\AiService;
use App\Models\Cpu;
use App\Models\Motherboard;
use App\Models\Ram;
use App\Models\Gpu;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function buildSpec($text)
    {
        $result = AiService::getSpec($text);
        $clean = preg_replace('/```(json)?|```/', '', $result);
        return json_decode($clean, true);
    }

    public function minimumPrice($text = null){
        $text = "Build a pc for programming";
        $pcDetails = $this->buildSpec($text);
        $minimumReq = $pcDetails["minimum_required_specs"];
        $budgetDistribution = $pcDetails["budget_distribution"];

        $cpu = $minimumReq['cpu'];
        $motherboard = $minimumReq['motherboard'];
        $cpu_cooler = $minimumReq['cpu_cooler'];
        $ram = $minimumReq['ram'];
        $storage = $minimumReq['storage'];
        $gpu = $minimumReq['gpu'];
        $psu = $minimumReq['psu'];
        $pc_case = $minimumReq['pc_case'];

        $allCpu = Cpu::whereRaw("REPLACE(socket, ' ', '') ILIKE ?", [str_replace(' ', '', $cpu['socket'])])
        ->where('core_count', '>=', $cpu['core_count_min'])
        ->where('boost_clock', '>=', $cpu['boost_clock_min_ghz'])
        ->get();

        $allMotherboard = Motherboard::whereRaw("REPLACE(socket_cpu, ' ', '') ILIKE ?", [str_replace(' ', '', $motherboard['socket_cpu'])])
            ->where('chipset', 'ILIKE', '%' . $motherboard['chipset_family'] . '%')
            ->where('form_factor', $motherboard['form_factor'])
            ->where('pcie_x16_slots', '>=', $motherboard['pcie_slots'])
            ->where('memory_type', $motherboard['memory_type'])
            ->where('memory_slots', '>=', $motherboard['memory_slots'])
            ->get();
        
        $allRam = Ram::where('form_factor', 'ILIKE', '%' . $ram['type'] . '%')
        ->whereRaw("
            (CAST(SPLIT_PART(modules, ' x ', 1) AS INTEGER) *
            CAST(REGEXP_REPLACE(SPLIT_PART(modules, ' x ', 2), 'GB', '', 'g') AS INTEGER)
            ) >= ?
        ", [$ram['capacity_min_gb']])
        ->whereRaw("
            CAST(SPLIT_PART(speed, '-', 2) AS INTEGER) >= ?
        ", [$ram['min_speed']])
        ->get();

        $allGpu = Gpu::where('memory','>=',$gpu['recommended_vram_gb'])
        ->where('length','<=',$gpu['max_length_mm'])->get();

        dd($minimumReq,count($allCpu),count($allMotherboard),count($allRam),$allGpu);
    }

    public function AiChatbot(Request $request){
        $build = $request->input('build');
        $question = $request->input('question');
        $result = AiService::askAI($build,$question);
        $clean = preg_replace('/```(json)?|```/', '', $result);
        $response = json_decode($clean, true);
        return response()->json(['message' => $response ]);
    }
}
