<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;

class CategoryPrice extends Model
{
    protected $table = 'category_price';
    protected $fillable = ['min_price', 'categ_id'];

    public function categprice(){
        return $this->belongsTo(Category::class, 'categ_id');
    }

    public $timestamps = false;
}
