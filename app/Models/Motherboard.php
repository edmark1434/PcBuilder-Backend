<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Motherboard extends Model
{
    use HasFactory;

    protected $table = 'motherboard';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'image_url',
        'product_url',
        'price',
        'manufacturer',
        'part_number',
        'socket_cpu',
        'form_factor',
        'chipset',
        'memory_max',
        'memory_type',
        'memory_slots',
        'memory_speed',
        'color',
        'pcie_x16_slots',
        'pcie_x8_slots',
        'pcie_x4_slots',
        'pcie_x1_slots',
        'pci_slots',
        'm2_slots',
        'mini_pcie_slots',
        'half_mini_pcie_slots',
        'mini_pcie_msata_slots',
        'msata_slots',
        'sata_6gb_s',
        'onboard_ethernet',
        'onboard_video',
        'usb_2_headers',
        'usb_2_single_headers',
        'usb_3_gen1_headers',
        'usb_3_gen2_headers',
        'usb_3_gen2x2_headers',
        'supports_ecc',
        'wireless_networking',
        'raid_support',
    ];

    protected $casts = [
        'memory_slots' => 'integer',
        'pcie_x16_slots' => 'integer',
        'pcie_x8_slots' => 'integer',
        'pcie_x4_slots' => 'integer',
        'pcie_x1_slots' => 'integer',
        'pci_slots' => 'integer',
        'mini_pcie_slots' => 'integer',
        'half_mini_pcie_slots' => 'integer',
        'msata_slots' => 'integer',
        'usb_2_headers' => 'integer',
        'usb_2_single_headers' => 'integer',
        'usb_3_gen1_headers' => 'integer',
        'usb_3_gen2_headers' => 'integer',
        'usb_3_gen2x2_headers' => 'integer',
        'supports_ecc' => 'boolean',
        'raid_support' => 'boolean',
    ];
}
