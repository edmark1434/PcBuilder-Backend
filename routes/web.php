<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use App\Http\Controllers\FavoriteController;

Route::get('/', function () {
    return view('welcome');
});