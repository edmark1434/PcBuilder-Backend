<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cpu extends Model
{
    use HasFactory;

    protected $table = 'cpu';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'manufacturer',
        'part_number',
        'socket',
        'core_count',
        'base_clock',
        'boost_clock',
        'tdp',
        'integrated_graphics',
        'max_memory',
        'ecc_support',
        'price',
        'ram_type',
        'ram_max_capacity',
        'ram_max_speed',
    ];

    protected $casts = [
        'core_count' => 'integer',
        'ecc_support' => 'boolean',
        'ram_max_capacity' => 'integer',
        'ram_max_speed' => 'integer',
    ];
}
