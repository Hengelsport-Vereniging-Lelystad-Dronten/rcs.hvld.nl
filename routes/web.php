<?php

/**
 * routes/web.php
 *
 * Register web routes voor de applicatie.
 *
 * Dit bestand bevat de routes die HTML/Inertia-views teruggeven
 * en typische webmiddleware (sessies, CSRF, auth) gebruiken.
 * Commentaar en secties in dit bestand zijn in het Nederlands
 * om snellere navigatie en onderhoud door het team te ondersteunen.
 */


use App\Http\Controllers\AanmeldingController;
use App\Http\Controllers\ControleRondeController;
use App\Http\Controllers\VisplannerController;
use App\Http\Controllers\OvertredingController;
use App\Http\Controllers\WaterQuickAddController;
use App\Http\Controllers\UitlegController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KaartController;
use App\Http\Controllers\Beheer\AuditLogController;
use App\Http\Controllers\Beheer\StrafmaatController;
use App\Http\Controllers\Beheer\ReportsController;
use App\Http\Controllers\Api\OverlastMeldingApiController;
use App\Models\OverlastMelding;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Zorg ervoor dat de controller geïmporteerd is voor gebruik buiten de closures


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Hier worden alle webroutes van de applicatie geregistreerd.
| De routes worden geladen door de RouteServiceProvider binnen een groep
| die de sessiestatus, CSRF-beveiliging en andere standaardmiddleware bevat.
|
*/

// PUBLIEKE ROUTES
// Publieke waterkaart, toegankelijk zonder inloggen.
Route::get('/visplanner', [VisplannerController::class, 'index'])->name('visplanner.index');

// Privacyverklaring
Route::get('/privacy', function () {
    return Inertia::render('Privacy');
})->name('privacy');

// Security / Responsible Disclosure
Route::get('/security', function () {
    return Inertia::render('Security');
})->name('security');

// Overlast Meldingen (Publiek meldformulier voor sportvisserij en dierenwelzijn)
Route::prefix('/overlast-meldingen')->name('overlast-meldingen.')->group(function () {
    // Formulier pagina
    Route::get('/', function () {
        return Inertia::render('OverlastMeldingen/Create', [
            'categories' => \App\Models\OverlastMelding::categories(),
        ]);
    })->name('create');

    // Bedankt pagina
    Route::get('/bedankt', function () {
        return Inertia::render('OverlastMeldingen/Bedankt');
    })->name('bedankt');
});

// Aanmeldformulier Sportvisserijcontroleur
Route::get('/aanmeldformulier', [AanmeldingController::class, 'create'])->name('aanmelden.create');
Route::post('/aanmeldformulier', [AanmeldingController::class, 'store'])->name('aanmelden.store');
Route::get('/aanmeldformulier/bedankt', [AanmeldingController::class, 'bedankt'])->name('aanmelden.bedankt');

// --- GROEP 1: Basis en Functionele Routes voor ELKE ingelogde gebruiker ---
// Deze groep combineert de basisfunctionaliteit (Dashboard/Profiel) en de
// dagelijkse taken (Controles, Overtredingen). Er is GEEN verplichte
// e-mailverificatie ('verified') meer nodig na de login, aangezien de
// accounts handmatig worden beheerd/geprovisioneerd.
Route::middleware('auth')->group(function () {
    // Standaard root route ('/'). Dit is het hoofddashboard dat toegankelijk is voor elke ingelogde gebruiker.
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Profiel bewerken: Toont het formulier om profielgegevens aan te passen.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Profiel bijwerken: Verwerkt de PATCH-request om profielgegevens op te slaan.
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Profiel verwijderen: Verwerkt de DELETE-request om het account te deactiveren/verwijderen.
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- CONTROLE EN RAPPORTAGE FUNCTIONALITEIT ---

    // CONTROLE RONDES (Index, Show, Store, Update, Destroy)
    // Bevat routes voor het overzicht, starten, bekijken en bewerken van controlerondes.
    Route::resource('controles', ControleRondeController::class);

    // CUSTOM ACTIE: RONDE AFRONDEN
    // Route om een specifieke controleronde officieel af te ronden (PUT/UPDATE actie).
    Route::put('controles/{controle_ronde}/afronden', [ControleRondeController::class, 'afronden'])
        ->name('controles.afronden');

    // OVERTREDINGEN (Alleen de store-actie)
    // Route voor het aanmaken van een nieuwe overtreding (POST) binnen een controleronde.
    Route::post('/overtredingen', [OvertredingController::class, 'store'])->name('overtredingen.store');
    Route::put('/overtredingen/{overtreding}', [OvertredingController::class, 'update'])->name('overtredingen.update');
    Route::put('/overtredingen/{overtreding}/annuleer', [OvertredingController::class, 'annuleer'])->name('overtredingen.annuleer');

    // CUSTOM ACTIE: SNEL WATER TOEVOEGEN
    // Route voor een snelle POST-actie om een nieuw water (locatie) toe te voegen vanuit de controle-flow.
    Route::post('/waters/store-quick', [WaterQuickAddController::class, 'store'])->name('waters.store-quick');

    // UITLEG SECTIE
    Route::get('/uitleg', [UitlegController::class, 'index'])->name('uitleg.index');
    Route::get('/uitleg/kaart', [UitlegController::class, 'kaart'])->name('uitleg.kaart');
    Route::get('/uitleg/faq', [FaqController::class, 'index'])->name('uitleg.faq');
    Route::get('/uitleg/overtredingen', [UitlegController::class, 'overtredingen'])->name('uitleg.overtredingen');
    Route::get('/uitleg/handleidingen', [UitlegController::class, 'handleidingen'])->name('uitleg.handleidingen');
});


// --- GROEP 2: BEHEER GEDEELTE (Management) ---
// Deze routes vereisen authenticatie ('auth') en de specifieke Laravel "gate" of "policy" genaamd 'beheerder'
// om de toegang te autoriseren. E-mailverificatie is verwijderd.
Route::middleware(['auth', 'beheerder'])->group(function () {
    // Basis Beheer Dashboard: Hoofdpagina voor beheerders met managementoverzichten.
    Route::get('/beheer', [App\Http\Controllers\BeheerController::class, 'index'])->name('beheer.index');

    // PERIODIEKE RAPPORTAGES
    // Routes voor beheerders om wekelijks, maandelijks, kwartaal en custom rapporten in te zien en te downloaden.
    // NIEUW: Statistieken Dashboard (vervangt de oude index)
    Route::get('beheer/reports', [ReportsController::class, 'index'])->name('beheer.reports.index');
    // NIEUW: PDF Download voor totaalrapportage
    Route::get('beheer/reports/download', [ReportsController::class, 'downloadReportPdf'])->name('beheer.reports.download');
    // NIEUW: Detailpagina voor recidivisten
    Route::get('beheer/reports/recidivist/{vispasnummer}', [ReportsController::class, 'recidivist'])->name('beheer.reports.recidivist');
    // NIEUW: PDF Download voor recidivisten
    Route::get('beheer/reports/recidivist/{vispasnummer}/pdf', [ReportsController::class, 'downloadRecidivistPdf'])->name('beheer.reports.recidivist.pdf');

    Route::resource('beheer/reports', \App\Http\Controllers\ReportController::class)
        ->only(['show', 'destroy'])
        ->names('beheer.reports');
    Route::post('/beheer/reports/generate', [\App\Http\Controllers\ReportController::class, 'generate'])->name('beheer.reports.generate');
    Route::get('/beheer/reports/{report}/download', [\App\Http\Controllers\ReportController::class, 'download'])->name('beheer.reports.download');

    // EXPORT OVERTREDINGEN
    Route::get('beheer/export-overtredingen', [App\Http\Controllers\BeheerController::class, 'exportOvertredingenIndex'])->name('beheer.export-overtredingen.index');
    Route::post('beheer/export-overtredingen/preview', [App\Http\Controllers\BeheerController::class, 'exportOvertredingenPreview'])->name('beheer.export-overtredingen.preview');
    Route::match(['get', 'post'], 'beheer/export-overtredingen/pdf', [App\Http\Controllers\BeheerController::class, 'exportOvertredingenPdf'])->name('beheer.export-overtredingen.pdf');
    Route::post('beheer/export-overtredingen/reset', [App\Http\Controllers\BeheerController::class, 'resetExportStatus'])->name('beheer.export-overtredingen.reset');
    Route::patch('beheer/export-overtredingen/{overtreding}/status', [App\Http\Controllers\BeheerController::class, 'updateExportStatus'])->name('beheer.export-overtredingen.update-status');

    // EXPORTS OVERZICHT
    Route::get('beheer/exports', [App\Http\Controllers\BeheerController::class, 'exportsIndex'])->name('beheer.exports.index');
    Route::get('beheer/exports/{export}/download', [App\Http\Controllers\BeheerController::class, 'downloadExport'])->name('beheer.exports.download');


    // GEBRUIKERS BEHEER (CRUD)
    // Resource routes voor het beheren van gebruikers (overzicht, aanmaken, bewerken, verwijderen).
    Route::resource('beheer/users', App\Http\Controllers\Beheer\UserController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->names('beheer.users');

    // WATEREN BEHEER (CRUD)
    // Resource routes voor het beheren van waterlocaties.
    Route::resource('beheer/waters', App\Http\Controllers\Beheer\WaterController::class)
        ->names('beheer.waters');

    // OVERTREDING TYPES BEHEER (CRUD)
    // Resource routes voor het beheren van de definities van overtredingstypes.
    Route::resource('beheer/overtreding_types', App\Http\Controllers\Beheer\OvertredingTypeController::class)
        ->names('beheer.overtreding_types');
        
    // STRAFMATEN BEHEER (CRUD) - INCLUSIEF NIEUWE VOLGORDE ROUTE
    Route::resource('beheer/strafmaten', StrafmaatController::class) // Gebruik de geimporteerde controller
        ->names('beheer.strafmaten');
    
    /**
     * ROUTE VOOR DRAG & DROP VOLGORDE
     * Dit endpoint verwerkt het POST-verzoek van de Vue-component
     * om de order_id's van de strafmaten in één keer bij te werken.
     * De route is expliciet gedefinieerd om de 'updateOrder' methode aan te roepen.
     * NB: Deze moet BINNEN de 'beheerder' middleware groep staan!
     */
    Route::post('beheer/strafmaten/order', [StrafmaatController::class, 'updateOrder'])
        ->name('beheer.strafmaten.updateOrder');

    // FAQ BEHEER (CRUD)
    Route::resource('beheer/faqs', App\Http\Controllers\Beheer\FaqController::class)
        ->names('beheer.faqs');

    // AUDIT LOG
    // Route voor het weergeven van het audit log.
    Route::get('/beheer/auditlog', [AuditLogController::class, 'index'])->name('beheer.auditlog.index');

    /**
     * OVERLAST MELDINGEN BEHEER
     * 
     * Beheerinterface voor meldingen over sportvisserij en dierenwelzijn.
     * Beheerders kunnen meldingen inzien, filteren, status wijzigen en afwijzen.
     */
    Route::prefix('beheer/overlast-meldingen')->name('beheer.overlast-meldingen.')->group(function () {
        // Overzicht van alle meldingen
        Route::get('/', function (Request $request) {
            $query = \App\Models\OverlastMelding::with('verwerktDoor')->latest();

            // Optionele filter: status (nieuw, in_behandeling, afgehandeld, afgewezen)
            if ($request->has('status')) {
                if ($request->get('status') === 'all') {
                    // Laat alle statussen zien wanneer expliciet gekozen.
                } elseif (in_array($request->get('status'), \App\Models\OverlastMelding::statuses())) {
                    $query->where('status', $request->get('status'));
                } else {
                    // Onbekende statusparameter wordt genegeerd en we vallen terug op de standaardweergave.
                    $query->whereIn('status', [\App\Models\OverlastMelding::STATUS_NIEUW, \App\Models\OverlastMelding::STATUS_IN_BEHANDELING]);
                }
            } else {
                // Standaard alleen nieuwe en in behandeling tonen
                $query->whereIn('status', [\App\Models\OverlastMelding::STATUS_NIEUW, \App\Models\OverlastMelding::STATUS_IN_BEHANDELING]);
            }

            // Optionele filter: categorie
            if ($request->has('categorie') && in_array($request->get('categorie'), \App\Models\OverlastMelding::categories())) {
                $query->where('categorie', $request->get('categorie'));
            }

            $perPage = min(max((int)$request->get('per_page', 15), 5), 100);
            $meldingen = $query->paginate($perPage)->withQueryString();

            return Inertia::render('Beheer/OverlastMeldingen/Index', [
                'meldingen' => $meldingen,
                'filters' => [
                    'status' => $request->get('status', 'all'),
                    'categorie' => $request->get('categorie', 'all'),
                    'per_page' => $perPage,
                ],
            ]);
        })->name('index');

        // Detailpagina van melding
        Route::get('{melding}', function (\App\Models\OverlastMelding $melding) {
            return Inertia::render('Beheer/OverlastMeldingen/Show', [
                'melding' => $melding->load('verwerktDoor'),
            ]);
        })->name('show');

        // Status update via beheerwebroute (auth + beheerder middleware)
        Route::patch('{melding}/status', function (Request $request, OverlastMelding $melding) {
            return app(OverlastMeldingApiController::class)->updateStatus($request, $melding);
        })->name('update-status');
    });
});


// Laadt de routes die nodig zijn voor de Laravel Breeze (of vergelijkbare) authenticatie (login, register, etc.).
require __DIR__.'/auth.php';
