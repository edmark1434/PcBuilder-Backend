<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gpu extends Model
{
    use HasFactory;

    protected $table = 'gpu';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'image_url',
        'product_url',
        'price',
        'manufacturer',
        'part_number',
        'chipset',
        'memory',
        'memory_type',
        'core_clock',
        'boost_clock',
        'effective_memory_clock',
        'interface',
        'color',
        'frame_sync',
        'length',
        'tdp',
        'case_expansion_slot_width',
        'total_slot_width',
        'cooling',
        'external_power',
        'hdmi_outputs',
        'displayport_outputs',
        'dvi_d_dual_link_outputs',
        'hdmi_2_1a_outputs',
        'displayport_1_4_outputs',
        'displayport_1_4a_outputs',
        'displayport_2_1_outputs',
        'sli_crossfire'
    ];

    protected $casts = [
        'case_expansion_slot_width' => 'integer',
        'total_slot_width' => 'integer',
        'hdmi_outputs' => 'integer',
        'displayport_outputs' => 'integer',
        'dvi_d_dual_link_outputs' => 'integer',
        'hdmi_2_1a_outputs' => 'integer',
        'displayport_1_4_outputs' => 'integer',
        'displayport_1_4a_outputs' => 'integer',
        'displayport_2_1_outputs' => 'integer',
    ];
}
