<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Psu extends Model
{
    use HasFactory;

    protected $table = 'psu';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'image_url',
        'product_url',
        'price',
        'manufacturer',
        'model',
        'part_number',
        'type',
        'efficiency_rating',
        'wattage',
        'length',
        'modular',
        'color',
        'fanless',
        'atx_4pin_connectors',
        'eps_8pin_connectors',
        'pcie_12plus4pin_12vhpwr_connectors',
        'pcie_12pin_connectors',
        'pcie_8pin_connectors',
        'pcie_6plus2pin_connectors',
        'pcie_6pin_connectors',
        'sata_connectors',
        'molex_4pin_connectors',
        'specs_number'
    ];

    protected $casts = [
        'fanless' => 'boolean',
        'atx_4pin_connectors' => 'integer',
        'eps_8pin_connectors' => 'integer',
        'pcie_12plus4pin_12vhpwr_connectors' => 'integer',
        'pcie_12pin_connectors' => 'integer',
        'pcie_8pin_connectors' => 'integer',
        'pcie_6plus2pin_connectors' => 'integer',
        'pcie_6pin_connectors' => 'integer',
        'sata_connectors' => 'integer',
        'molex_4pin_connectors' => 'integer',
    ];
}
