<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
class PcController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function categoryList()
    {
        $categories = Category::with('categprice')->get()->map(function ($categ) {
            return [
                $categ->name => $categ->categprice->min_price
            ];
        });
        if(!$categories){
            return response()->json(['message' => 'No categories available'],200);
        }
        return response()->json($categories,200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
