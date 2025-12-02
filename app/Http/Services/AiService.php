<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    public static function getSpec($promptText = "Build a budget gaming PC")
    {
        $systemMessage = [
            "role" => "system",
            "content" => [
                [
                    "type" => "text",
                    "text" =>
                        "You are a professional PC builder. Your job is to generate a fully compatible specification based ONLY on the database columns provided to you. You MUST output ONLY valid JSON. Never explain anything. Never output text outside the JSON.\n\n" .

                        "Use ONLY these columns when determining compatibility:\n\n" .
                        "CPU:\n" .
                        "- socket\n- core_count\n- boost_clock\n- integrated_graphics\n- ram_type\n- ram_max_capacity\n- ram_max_speed\n\n" .

                        "Motherboard:\n" .
                        "- socket_cpu\n- form_factor\n- chipset\n- memory_type\n- memory_slots\n- memory_speed\n- pcie_x16_slots\n- m2_slots\n\n" .

                        "RAM:\n" .
                        "- speed\n- form_factor\n- modules\n- capacity (derived from 'modules')\n- type (derived from speed)\n\n" .

                        "GPU:\n" .
                        "- length\n- memory (GB)\n- interface\n\n" .

                        "Storage:\n" .
                        "- capacity_gb\n- form_factor\n- interface\n- is_nvme\n\n" .

                        "CPU Cooler:\n" .
                        "- cpu_socket\n- height\n\n" .

                        "PSU:\n" .
                        "- wattage\n- efficiency_rating\n- pcie_6pin_connectors\n- pcie_8pin_connectors\n- pcie_12pin_connectors\n- pcie_12plus4pin_12vhpwr_connectors\n\n" .

                        "PC Case:\n" .
                        "- motherboard_form_factor\n- maximum_video_card_length\n- dimensions (cooler height limit extracted if possible)\n- psu_length (if available)\n- type\n\n" .

                        "PART COMPATIBILITY RULES YOU MUST FOLLOW:\n" .
                        "- CPU.socket MUST match Motherboard.socket_cpu.\n" .
                        "- CPU.ram_type MUST match Motherboard.memory_type.\n" .
                        "- RAM.speed MUST match or be below Motherboard.memory_speed max.\n" .
                        "- RAM.form_factor MUST be 288-pin DIMM for DDR4/DDR5.\n" .
                        "- GPU.length MUST be <= PC case maximum_video_card_length.\n" .
                        "- Motherboard.form_factor MUST be supported by PC case.\n" .
                        "- CPU cooler height MUST be <= case cooler height limit.\n" .
                        "- Storage.interface MUST match motherboard M.2 or SATA support.\n" .
                        "- PSU wattage MUST be >= 550W minimum.\n" .
                        "- PSU PCIe connectors MUST meet GPU external_power requirements.\n\n" .

                        "You MUST always respond ONLY in the following JSON format:\n\n" .
                        "{\n" .
                        '  "minimum_required_specs": {' . "\n" .
                        '    "cpu": {"socket": "", "core_count_min": 0, "boost_clock_min_ghz": 0.0, "integrated_graphics_required": false},' . "\n" .
                        '    "motherboard": {"socket_cpu": "", "chipset_family": "", "form_factor": "", "pcie_slots": 0, "memory_type": "", "memory_slots": 0},' . "\n" .
                        '    "ram": {"type": "", "min_speed": 0, "capacity_min_gb": 0, "pins": ""},' . "\n" .
                        '    "gpu": {"max_length_mm": 0, "recommended_vram_gb": 0},' . "\n" .
                        '    "storage": {"interface": "", "form_factor": "", "nvme_required": false, "capacity_min_gb": 0},' . "\n" .
                        '    "cpu_cooler": {"supported_sockets": "", "max_height_mm": 0},' . "\n" .
                        '    "psu": {"wattage_min": 0, "efficiency_rating_min": "", "pcie_connectors_required": {"pin_6": 0, "pin_8": 0, "pin_12": 0, "pin_12vhpwr": 0}},' . "\n" .
                        '    "pc_case": {"motherboard_form_factor": "", "gpu_max_length_mm": 0, "dimensions": ""}' . "\n" .
                        "  },\n" .
                        "I need to have each part has a percent in the budget distribution. I want a balanced and fair distribution based on category. Return integer".
                        '  "budget_distribution": {' . "\n" .
                        '    "cpu_percent": 0,' . "\n" .
                        '    "gpu_percent": 0,' . "\n" .
                        '    "ram_percent": 0,' . "\n" .
                        '    "storage_percent": 0,' . "\n" .
                        '    "motherboard_percent": 0,' . "\n" .
                        '    "psu_percent": 0,' . "\n" .
                        '    "cpu_cooler_percent": 0,' . "\n" .
                        '    "pc_case_percent": 0' . "\n" .
                        "  }\n" .
                        "}\n\n" .

                        "Fill in the values based on the user's prompt and total budget. Never include explanations. Never output anything other than valid JSON."
                ]
            ]
        ];


        $userMessage = [
            "role" => "user",
            "content" => [
                ["type" => "text", "text" => $promptText]
            ]
        ];
        return self::responseChat($systemMessage,$userMessage);
    }
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
            $buildText .= strtoupper($part) . " = " . $partName . "\n";
        }

        $systemMessage = [
            "role" => "system",
            "content" => "You are a professional salesman/tech expert. Provide details and answer any questions about the following PC build:\n\n" 
                . $buildText .
                "\nYou MUST always respond ONLY in the following JSON format:\n\n" .
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