<?php

use App\Http\Controllers\AanmeldingController;
use App\Http\Controllers\ControleRondeController;
use App\Http\Controllers\VisplannerController;
use App\Http\Controllers\OvertredingController;
use App\Http\Controllers\VispasScanController;
use App\Http\Controllers\WaterQuickAddController;
use App\Http\Controllers\UitlegController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Beheer\AuditLogController;
use App\Http\Controllers\Beheer\OverlastMeldingController;
use App\Http\Controllers\Beheer\StrafmaatController;
use App\Http\Controllers\Beheer\ReportsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


// =============================
// PUBLIEKE ROUTES
// =============================

Route::get('/visplanner', [VisplannerController::class, 'index'])->name('visplanner.index');

Route::view('/privacy', 'Privacy')->name('privacy');
Route::view('/security', 'Security')->name('security');

Route::get('/session/keep-alive', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'authenticated' => auth()->check(),
    ]);
})->name('session.keep-alive');


// Overlast meldingen (publiek)
Route::prefix('overlast-meldingen')->name('overlast-meldingen.')->group(function () {

    Route::get('/', function () {
        return Inertia::render('OverlastMeldingen/Create', [
            'categories' => \App\Models\OverlastMelding::categories(),
        ]);
    })->name('create');

    Route::get('/bedankt', fn () => Inertia::render('OverlastMeldingen/Bedankt'))
        ->name('bedankt');
});


// Aanmeldingen
Route::get('/aanmeldformulier', [AanmeldingController::class, 'create'])->name('aanmelden.create');
Route::post('/aanmeldformulier', [AanmeldingController::class, 'store'])->name('aanmelden.store');
Route::get('/aanmeldformulier/bedankt', [AanmeldingController::class, 'bedankt'])->name('aanmelden.bedankt');


// =============================
// AUTH GROUP
// =============================

Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Profiel
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Controle rondes
    Route::resource('controles', ControleRondeController::class);

    Route::put('controles/{controle_ronde}/afronden', [ControleRondeController::class, 'afronden'])
        ->name('controles.afronden');

    // Overtredingen
    Route::post('/overtredingen', [OvertredingController::class, 'store'])->name('overtredingen.store');
    Route::put('/overtredingen/{overtreding}', [OvertredingController::class, 'update'])->name('overtredingen.update');
    Route::put('/overtredingen/{overtreding}/annuleer', [OvertredingController::class, 'annuleer'])->name('overtredingen.annuleer');

    Route::post('/vispas/scan', [VispasScanController::class, 'store'])->name('vispas.scan');

    Route::post('/waters/store-quick', [WaterQuickAddController::class, 'store'])
        ->name('waters.store-quick');

    // Uitleg
    Route::prefix('uitleg')->name('uitleg.')->group(function () {
        Route::get('/', [UitlegController::class, 'index'])->name('index');
        Route::get('/kaart', [UitlegController::class, 'kaart'])->name('kaart');
        Route::get('/faq', [FaqController::class, 'index'])->name('faq');
        Route::get('/overtredingen', [UitlegController::class, 'overtredingen'])->name('overtredingen');
        Route::get('/handleidingen', [UitlegController::class, 'handleidingen'])->name('handleidingen');
    });
});


// =============================
// BEHEER (ADMIN)
// =============================

Route::middleware(['auth', 'beheerder'])->prefix('beheer')->name('beheer.')->group(function () {

    Route::get('/', [App\Http\Controllers\BeheerController::class, 'index'])->name('index');


    // === REPORTS ===

    Route::prefix('reports')->name('reports.')->group(function () {

        Route::get('/', [ReportsController::class, 'index'])->name('index');

        // ✅ FIX: unieke namen
        Route::get('/download', [ReportsController::class, 'downloadReportPdf'])
            ->name('download.all');

        Route::get('/recidivist/{vispasnummer}', [ReportsController::class, 'recidivist'])
            ->name('recidivist');

        Route::get('/recidivist/{vispasnummer}/pdf', [ReportsController::class, 'downloadRecidivistPdf'])
            ->name('recidivist.pdf');

        // Resource (los gehouden)
        Route::resource('/', \App\Http\Controllers\ReportController::class)
            ->parameter('', 'report')
            ->only(['show', 'destroy']);

        Route::post('/generate', [\App\Http\Controllers\ReportController::class, 'generate'])
            ->name('generate');

        Route::get('/{report}/download', [\App\Http\Controllers\ReportController::class, 'download'])
            ->name('download.single');
    });


    // === EXPORTS ===

    Route::get('export-overtredingen', [App\Http\Controllers\BeheerController::class, 'exportOvertredingenIndex'])
        ->name('export-overtredingen.index');

    Route::post('export-overtredingen/preview', [App\Http\Controllers\BeheerController::class, 'exportOvertredingenPreview'])
        ->name('export-overtredingen.preview');

    Route::match(['get', 'post'], 'export-overtredingen/pdf', [App\Http\Controllers\BeheerController::class, 'exportOvertredingenPdf'])
        ->name('export-overtredingen.pdf');

    Route::post('export-overtredingen/reset', [App\Http\Controllers\BeheerController::class, 'resetExportStatus'])
        ->name('export-overtredingen.reset');

    Route::patch('export-overtredingen/{overtreding}/status', [App\Http\Controllers\BeheerController::class, 'updateExportStatus'])
        ->name('export-overtredingen.update-status');


    Route::get('exports', [App\Http\Controllers\BeheerController::class, 'exportsIndex'])
        ->name('exports.index');

    Route::get('exports/{export}/download', [App\Http\Controllers\BeheerController::class, 'downloadExport'])
        ->name('exports.download');


    // === CRUD ===

    Route::resource('users', App\Http\Controllers\Beheer\UserController::class)->names('users');
    Route::resource('waters', App\Http\Controllers\Beheer\WaterController::class)->names('waters');
    Route::resource('overtreding_types', App\Http\Controllers\Beheer\OvertredingTypeController::class)->names('overtreding_types');
    Route::resource('strafmaten', StrafmaatController::class)->names('strafmaten');

    Route::post('strafmaten/order', [StrafmaatController::class, 'updateOrder'])
        ->name('strafmaten.updateOrder');

    Route::resource('faqs', App\Http\Controllers\Beheer\FaqController::class)->names('faqs');


    // === OVERIGE ===

    Route::get('auditlog', [AuditLogController::class, 'index'])->name('auditlog.index');

    Route::prefix('overlast-meldingen')->name('overlast-meldingen.')->group(function () {
        Route::get('/', [OverlastMeldingController::class, 'index'])->name('index');
        Route::get('{melding}', [OverlastMeldingController::class, 'show'])->name('show');
        Route::patch('{melding}/status', [OverlastMeldingController::class, 'updateStatus'])->name('update-status');
    });
});


// =============================
// AUTH ROUTES
// =============================

require __DIR__.'/auth.php';