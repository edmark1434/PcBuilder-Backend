<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuCooler extends Model
{
    use HasFactory;

    protected $table = 'cpu_cooler';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'image_url',
        'product_url',
        'price',
        'manufacturer',
        'model',
        'part_number',
        'fan_rpm',
        'noise_level',
        'color',
        'height',
        'cpu_socket',
        'water_cooled',
        'fanless',
        'specs_number'
    ];

    protected $casts = [
        'water_cooled' => 'boolean',
        'fanless' => 'boolean',
    ];
}
