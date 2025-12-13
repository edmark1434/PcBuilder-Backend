<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PcPart extends Model
{
    use HasFactory;

    protected $table = 'pc_parts';

    protected $fillable = [
        'type',
        'external_id',
        'vendor',
        'title',
        'price',
        'image',
        'link'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get parts by type
     */
    public static function getByType($type)
    {
        return self::where('type', $type)->get();
    }

    /**
     * Get all parts grouped by type
     */
    public static function getAllGrouped()
    {
        return self::get()->groupBy('type')->toArray();
    }
}
