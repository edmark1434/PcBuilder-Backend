<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CategoryPrice;
class Category extends Model
{
    protected $table = 'category';
    protected $fillable = ['name'];

    public function categprice(){
        return $this->hasOne(CategoryPrice::class, 'categ_id');
    }

    public $timestamps = false;
}
