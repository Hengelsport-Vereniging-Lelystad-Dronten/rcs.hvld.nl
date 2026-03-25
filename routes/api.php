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
use App\Http\Controllers\Api\OverlastMeldingApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route om het dichtstbijzijnde water te vinden op basis van coördinaten.
Route::get('water/nearest', [WaterApiController::class, 'nearest'])
    ->name('api.water.nearest');    

// Zorg ervoor dat de route direct toegankelijk is (of de juiste authenticatie middleware heeft):
Route::post('/recidive-check', [RecidiveController::class, 'check'])
    ->name('api.recidive-check');

/**
 * OVERLAST MELDINGEN API
 * 
 * Publieke endpoints voor het indienen van meldingen over sportvisserij en dierenwelzijn.
 * Deze routes zijn OPENBAAR - geen authenticatie vereist voor het indienen van meldingen.
 */

// PUBLIEKE ENDPOINTS (geen auth)
Route::prefix('overlast-meldingen')->group(function () {
    // Indienen van een nieuwe melding (publiek)
    Route::post('/', [OverlastMeldingApiController::class, 'store'])
        ->name('api.overlast-meldingen.store');

    // Ophalen beschikbare categorieën (voor frontend dropdown)
    Route::get('categories', [OverlastMeldingApiController::class, 'categories'])
        ->name('api.overlast-meldingen.categories');
});

// BEHEERDER ENDPOINTS (met auth + beheerder gate)
Route::middleware(['auth', 'beheerder'])->prefix('overlast-meldingen')->group(function () {
    // Overzicht van alle meldingen
    Route::get('/', [OverlastMeldingApiController::class, 'index'])
        ->name('api.overlast-meldingen.index');

    // Detailweergave van één melding
    Route::get('{melding}', [OverlastMeldingApiController::class, 'show'])
        ->name('api.overlast-meldingen.show');

    // Wijzig status van melding
    Route::patch('{melding}/status', [OverlastMeldingApiController::class, 'updateStatus'])
        ->name('api.overlast-meldingen.update-status');

    // Verwijder melding
    Route::delete('{melding}', [OverlastMeldingApiController::class, 'destroy'])
        ->name('api.overlast-meldingen.destroy');

    // Ophalen beschikbare statussen (voor beheerders)
    Route::get('meta/statuses', [OverlastMeldingApiController::class, 'statuses'])
        ->name('api.overlast-meldingen.statuses');

    // Ophalen statistieken (voor beheerders dashboard)
    Route::get('meta/statistics', [OverlastMeldingApiController::class, 'statistics'])
        ->name('api.overlast-meldingen.statistics');
});

// Voorbeeld voor routes/api.php (indien je dit bestand hebt)
Route::middleware('auth:sanctum')->get('/reports/download', [\App\Http\Controllers\Beheer\ReportsController::class, 'downloadReportPdf']);
