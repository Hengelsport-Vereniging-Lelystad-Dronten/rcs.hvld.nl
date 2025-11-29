<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WaterApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route om het dichtstbijzijnde water te vinden op basis van coördinaten.
Route::get('water/nearest', [WaterApiController::class, 'nearest'])
    ->name('api.water.nearest');    