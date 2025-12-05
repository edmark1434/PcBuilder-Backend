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
class PcController extends Controller
{
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
}
