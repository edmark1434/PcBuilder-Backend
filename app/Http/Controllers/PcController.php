<?php

namespace App\Http\Controllers;

use App\Models\Cpu;
use App\Models\CpuCooler;
use App\Models\Gpu;
use App\Models\Motherboard;
use App\Models\PcCase;
use App\Models\Psu;
use App\Models\Ram;
use App\Models\Storage;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Services\AiService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Http\Services\BrevoEmailServices;


class PcController extends Controller
{
    public static $categorySpecs = [
        'Gaming' => [
            'cpu' => ['socket' => 'AM5', 'core_count_min' => 6, 'boost_clock_min_ghz' => 3.5],
            'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
            'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 16, 'min_speed' => 5200],
            'gpu' => ['max_length_mm' => 320, 'recommended_vram_gb' => 8],
            'gpu_required' => true,
            'storage' => ['is_nvme' => true, 'capacity_min_gb' => 1000,'type' => 'SSD'],
            'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 160, 'fan_rpm_min' => 800, 'fan_rpm_max' => 1800],
            'psu' => ['wattage_min' => 650],
            'pc_case' => ['motherboard_form_factor' => 'ATX']
        ],

        'School' => [
            'cpu' => ['socket' => 'AM5', 'core_count_min' => 4, 'boost_clock_min_ghz' => 3.0],
            'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 2],
            'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 8, 'min_speed' => 4800],
            'gpu' => ['max_length_mm' => 250, 'recommended_vram_gb' => 4],
            'gpu_required' => false,
            'storage' => ['is_nvme' => false, 'capacity_min_gb' => 500,'type' => 'SSD'],
            'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 140, 'fan_rpm_min' => 1900, 'fan_rpm_max' => 2300],
            'psu' => ['wattage_min' => 500],
            'pc_case' => ['motherboard_form_factor' => 'ATX']
        ],

        'Office Work' => [
            'cpu' => ['socket' => 'AM5', 'core_count_min' => 4, 'boost_clock_min_ghz' => 3.5],
            'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 2],
            'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 8, 'min_speed' => 4800],
            'gpu' => ['max_length_mm' => 250, 'recommended_vram_gb' => 0],
            'gpu_required' => false,
            'storage' => ['is_nvme' => false, 'capacity_min_gb' => 256, 'type' => 'SSD'],
            'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 140, 'fan_rpm_min' => 800, 'fan_rpm_max' => 2600],
            'psu' => ['wattage_min' => 400],
            'pc_case' => ['motherboard_form_factor' => 'ATX']
        ],

        'Video Editing' => [
            'cpu' => ['socket' => 'AM5', 'core_count_min' => 8, 'boost_clock_min_ghz' => 3.7],
            'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
            'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 32, 'min_speed' => 5200],
            'gpu' => ['max_length_mm' => 320, 'recommended_vram_gb' => 8],
            'gpu_required' => true,
            'storage' => ['is_nvme' => true, 'capacity_min_gb' => 2000,'type' => 'SSD'],
            'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 160, 'fan_rpm_min' => 1500, 'fan_rpm_max' => 2500],
            'psu' => ['wattage_min' => 750],
            'pc_case' => ['motherboard_form_factor' => 'ATX']
        ],

        'Programming' => [
            'cpu' => ['socket' => 'AM5', 'core_count_min' => 4, 'boost_clock_min_ghz' => 3.2],
            'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 2],
            'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 16, 'min_speed' => 4800],
            'gpu' => ['max_length_mm' => 250, 'recommended_vram_gb' => 4],
            'gpu_required' => false,
            'storage' => ['is_nvme' => false, 'capacity_min_gb' => 500,'type' => 'SSD'],
            'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 140, 'fan_rpm_min' => 500, 'fan_rpm_max' => 1850],
            'psu' => ['wattage_min' => 500],
            'pc_case' => ['motherboard_form_factor' => 'ATX']
        ],

        '3D Modeling' => [
            'cpu' => ['socket' => 'AM5', 'core_count_min' => 12, 'boost_clock_min_ghz' => 3.8],
            'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
            'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 64, 'min_speed' => 5200],
            'gpu' => ['max_length_mm' => 320, 'recommended_vram_gb' => 12],
            'gpu_required' => true,
            'storage' => ['is_nvme' => true, 'capacity_min_gb' => 2000,'type' => 'SSD'],
            'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 170, 'fan_rpm_min' => 800, 'fan_rpm_max' => 1500],
            'psu' => ['wattage_min' => 850],
            'pc_case' => ['motherboard_form_factor' => 'ATX']
        ],

        'Photo Editing' => [
            'cpu' => ['socket' => 'AM5', 'core_count_min' => 6, 'boost_clock_min_ghz' => 3.5],
            'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
            'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 16, 'min_speed' => 5200],
            'gpu' => ['max_length_mm' => 280, 'recommended_vram_gb' => 6],
            'gpu_required' => true,
            'storage' => ['is_nvme' => true, 'capacity_min_gb' => 1000,'type' => 'SSD'],
            'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 150, 'fan_rpm_min' => 800, 'fan_rpm_max' => 2600],
            'psu' => ['wattage_min' => 650],
            'pc_case' => ['motherboard_form_factor' => 'ATX']
        ],

        'Graphic Design' => [
            'cpu' => ['socket' => 'AM5', 'core_count_min' => 6, 'boost_clock_min_ghz' => 3.5],
            'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
            'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 16, 'min_speed' => 5200],
            'gpu' => ['max_length_mm' => 280, 'recommended_vram_gb' => 6],
            'gpu_required' => true,
            'storage' => ['is_nvme' => true, 'capacity_min_gb' => 1000,'type' => 'SSD'],
            'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 150, 'fan_rpm_min' => 500, 'fan_rpm_max' => 1850],
            'psu' => ['wattage_min' => 650],
            'pc_case' => ['motherboard_form_factor' => 'ATX']
        ],

        'Streaming' => [
            'cpu' => ['socket' => 'AM5', 'core_count_min' => 8, 'boost_clock_min_ghz' => 3.7],
            'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
            'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 32, 'min_speed' => 5200],
            'gpu' => ['max_length_mm' => 320, 'recommended_vram_gb' => 8],
            'gpu_required' => true,
            'storage' => ['is_nvme' => true, 'capacity_min_gb' => 2000,'type' => 'SSD'],
            'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 160, 'fan_rpm_min' => 1500, 'fan_rpm_max' => 2500],
            'psu' => ['wattage_min' => 750],
            'pc_case' => ['motherboard_form_factor' => 'ATX']
        ],

        'Content Creation' => [
            'cpu' => ['socket' => 'AM5', 'core_count_min' => 8, 'boost_clock_min_ghz' => 3.7],
            'motherboard' => ['socket_cpu' => 'AM5', 'form_factor' => 'ATX', 'memory_type' => 'DDR5', 'memory_slots' => 4],
            'ram' => ['type' => 'DDR5', 'capacity_min_gb' => 32, 'min_speed' => 5200],
            'gpu' => ['max_length_mm' => 320, 'recommended_vram_gb' => 8],
            'gpu_required' => true,
            'storage' => ['is_nvme' => true, 'capacity_min_gb' => 2000,'type' => 'SSD'],
            'cpu_cooler' => ['supported_sockets' => 'AM5', 'max_height_mm' => 160, 'fan_rpm_min' => 800, 'fan_rpm_max' => 1500],
            'psu' => ['wattage_min' => 750],
            'pc_case' => ['motherboard_form_factor' => 'ATX']
        ],
    ];
    /**
     * Display a listing of the resource.
     */
    public function categoryList()
    {
        $saveCateg = session('category');
        if($saveCateg){
            return response()->json($saveCateg,200);
        }
        $categories = Category::with('categprice')->get()->map(function ($categ) {
            return [
                $categ->name => $categ->categprice->min_price
            ];
        });
        if(!$categories){
            return response()->json(['message' => 'No categories available'],200);
        }
        session(['category' => $categories]);
        return response()->json($categories,200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function getCpu($id)
    {
        $cpu = Cpu::findOrFail($id);
        return response()->json($cpu, 200);
    }
    public function getCpuCooler($id)
    {
        $cpu_cooler = CpuCooler::findOrFail($id);
        return response()->json($cpu_cooler, 200);
    }
    public function getMotherboard($id)
    {
        $motherboard = Motherboard::findOrFail($id);
        return response()->json($motherboard, 200);
    }
    public function getRam($id)
    {
        $ram = Ram::findOrFail($id);
        return response()->json($ram, 200);
    }
    public function getStorage($id)
    {
        $storage = Storage::findOrFail($id);
        return response()->json($storage, 200);
    }
    public function getPsu($id)
    {
        $psu = Psu::findOrFail($id);
        return response()->json($psu, 200);
    }
    public function getPcCase($id)
    {
        $pc_case = PcCase::findOrFail($id);
        return response()->json($pc_case, 200);
    }
     public function getGpu($id)
    {
        $gpu = Gpu::findOrFail($id);
        return response()->json($gpu, 200);
    }
    public function sendForgetPasswordEmail(Request $request)
    {
        $email = $request->input('email');
        $six_digit_code = rand(100000, 999999);

        $brevo = new BrevoEmailServices();
        $brevo->sendResetCode($email, $six_digit_code);

        Cache::put('password_reset_' . $email, $six_digit_code, now()->addMinutes(5));
        return response()->json([
            'message' => 'Password reset email sent',
        ], 200);
    }

    public function verifyResetCode(Request $request)
    {
        $email = $request->input('email');
        $code = $request->input('code');

        $cachedCode = Cache::get('password_reset_' . $email);

        if ($cachedCode && $cachedCode == $code) {
            return response()->json([
                'message' => 'Code verified successfully',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Invalid or expired code',
            ], 400);
        }
    }
}
