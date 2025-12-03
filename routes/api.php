<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WaterApiController;
use App\Http\Controllers\Api\RecidiveController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route om het dichtstbijzijnde water te vinden op basis van coördinaten.
Route::get('water/nearest', [WaterApiController::class, 'nearest'])
    ->name('api.water.nearest');    

// Zorg ervoor dat de route direct toegankelijk is (of de juiste authenticatie middleware heeft):
Route::post('/recidive-check', [RecidiveController::class, 'check'])
    ->name('api.recidive-check');
