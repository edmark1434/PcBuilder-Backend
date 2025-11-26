<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ram extends Model
{
    use HasFactory;

    protected $table = 'ram';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'image_url',
        'product_url',
        'price',
        'manufacturer',
        'part_number',
        'speed',
        'form_factor',
        'modules',
        'price_per_gb',
        'color',
        'first_word_latency',
        'cas_latency',
        'voltage',
        'timing',
        'ecc_registered',
        'heat_spreader',
        'specs_number'
    ];

    protected $casts = [
        'first_word_latency' => 'integer',
        'cas_latency' => 'integer',
        'heat_spreader' => 'boolean',
    ];
}
