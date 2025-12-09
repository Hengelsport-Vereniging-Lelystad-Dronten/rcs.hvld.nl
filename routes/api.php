<?php

/**
 * routes/api.php
 *
 * API-routes die JSON responses teruggeven, gebruikt door frontend (AJAX/inertia requests)
 * of externe clients. Deze routes zitten vaak achter API-authenticatie (sanctum)
 * of zijn publiek wanneer expliciet aangegeven.
 */

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
