<?php

use App\Http\Controllers\ControleRondeController;
use App\Http\Controllers\OvertredingController;
use App\Http\Controllers\WaterQuickAddController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Zorg ervoor dat de controller geïmporteerd is voor gebruik buiten de closures
use App\Http\Controllers\Beheer\StrafmaatController;

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

    // CUSTOM ACTIE: SNEL WATER TOEVOEGEN
    // Route voor een snelle POST-actie om een nieuw water (locatie) toe te voegen vanuit de controle-flow.
    Route::post('/waters/store-quick', [WaterQuickAddController::class, 'store'])->name('waters.store-quick');
});


// --- GROEP 2: BEHEER GEDEELTE (Management) ---
// Deze routes vereisen authenticatie ('auth') en de specifieke Laravel "gate" of "policy" genaamd 'beheerder'
// om de toegang te autoriseren. E-mailverificatie is verwijderd.
Route::middleware(['auth', 'beheerder'])->group(function () {
    // Basis Beheer Dashboard: Hoofdpagina voor beheerders met managementoverzichten.
    Route::get('/beheer', [App\Http\Controllers\BeheerController::class, 'index'])->name('beheer.index');

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

});


// Laadt de routes die nodig zijn voor de Laravel Breeze (of vergelijkbare) authenticatie (login, register, etc.).
require __DIR__.'/auth.php';