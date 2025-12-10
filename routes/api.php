<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use App\Http\Controllers\PcController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavoriteController;

Route::post('/askAI',[MainController::class,'AiChatbot']);
Route::get('/build-spec',[MainController::class,'buildSpec']);
Route::post('/min-price',[MainController::class,'buildWithBudgetRange']);
Route::post('/build',[MainController::class,'getCategorySpecs']);
Route::post('/save-build',[MainController::class,'saveBuild']);
Route::get('/category',[PcController::class,'categoryList']);
Route::get('/cpu/{id}',[PcController::class,'getCpu']);
Route::get('/pc-case/{id}',[PcController::class,'getPcCase']);
Route::get('/cpu-cooler/{id}',[PcController::class,'getCpuCooler']);
Route::get('/ram/{id}', [PcController::class,'getRam']);
Route::get('/storage/{id}',[PcController::class,'getStorage']);
Route::get('/gpu/{id}',[PcController::class,'getGpu']);
Route::get('/psu/{id}',[PcController::class,'getPsu']);
Route::get('/pc-case/{id}',[PcController::class,'getPcCase']);
Route::get('/motherboard/{id}',[PcController::class,'getMotherboard']);
Route::get('/ram/{id}',[PcController::class,'getRam']);
Route::post('/login',[AuthController::class,'login']);
Route::post('/signup',[AuthController::class,'signup']);
Route::post('/change-password',[AuthController::class,'updatePassword']);
Route::post('/forget-password',[PcController::class,'sendForgetPasswordEmail']);
Route::post('/verify-reset-code',[PcController::class,'verifyResetCode']);
Route::get('/favorites', [FavoriteController::class, 'index']);
Route::post('/favorites', [FavoriteController::class, 'store']);
Route::delete('/favorites/{id}', [FavoriteController::class, 'destroy']);
Route::delete('/favorites', [FavoriteController::class, 'clear']);
