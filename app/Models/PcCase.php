<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PcCase extends Model
{
    use HasFactory;

    protected $table = 'pc_case';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'image_url',
        'product_url',
        'price',
        'manufacturer',
        'part_number',
        'type',
        'color',
        'power_supply',
        'side_panel',
        'power_supply_shroud',
        'front_panel_usb',
        'motherboard_form_factor',
        'maximum_video_card_length',
        'drive_bays',
        'expansion_slots',
        'dimensions',
        'volume'
    ];
}
