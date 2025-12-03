<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use App\Http\Controllers\PcController;

Route::post('/askAI',[MainController::class,'AiChatbot']);
Route::get('/build-spec',[MainController::class,'buildSpec']);
Route::post('/min-price',[MainController::class,'buildWithBudgetRange']);
Route::post('/save-build',[MainController::class,'saveBuild']);
Route::get('/category',[PcController::class,'categoryList']);
