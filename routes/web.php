<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
Route::get('/', function () {
    return view('welcome');
});
Route::get('/build-spec',[MainController::class,'buildSpec']);
Route::get('/min-price',[MainController::class,'minimumPrice']);

