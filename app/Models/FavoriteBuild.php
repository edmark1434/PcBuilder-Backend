<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteBuild extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'build_id',
        'total_price',
        'parts_data',
        'build_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'parts_data' => 'array',
        'build_data' => 'array',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the favorite build.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted parts data
     */
    public function getFormattedPartsAttribute()
    {
        return $this->parts_data ?? [];
    }

    /**
     * Get formatted build data
     */
    public function getFormattedBuildAttribute()
    {
        return $this->build_data ?? [];
    }

    /**
     * Get created at in human readable format
     */
    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at->format('M d, Y h:i A');
    }

    /**
     * Get updated at in human readable format
     */
    public function getUpdatedAtFormattedAttribute()
    {
        return $this->updated_at->format('M d, Y h:i A');
    }
}