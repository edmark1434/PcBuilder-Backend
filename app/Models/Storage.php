<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Storage extends Model
{
    use HasFactory;

    protected $table = 'storage';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'image_url',
        'product_url',
        'price',
        'manufacturer',
        'part_number',
        'capacity_gb',
        'price_per_gb',
        'type',
        'cache_mb',
        'form_factor',
        'interface',
        'is_nvme'
    ];

    protected $casts = [
        'is_nvme' => 'boolean',
    ];
}
