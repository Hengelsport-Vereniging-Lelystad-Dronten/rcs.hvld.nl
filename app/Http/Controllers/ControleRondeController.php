<?php

namespace App\Http\Controllers;

use App\Models\ControleRonde;
use App\Models\Water; // Nodig voor het startformulier
use App\Models\OvertredingType; // NIEUW: Importeer het model
use App\Models\Strafmaat; // NIEUW: Importeer het model voor de lijst met sancties
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Controller: ControleRondeController
 *
 * Beheert het starten, tonen, afronden en verwijderen van controle-rondes.
 * Deze controller levert data aan Inertia pages en zorgt dat gerelateerde modellen
 * (zoals `overtredingTypes` en `strafmaten`) beschikbaar zijn voor de Vue frontend.
 */
class ControleRondeController extends Controller
{
    /**
     * Toon een overzicht van alle controle-rondes.
     * Laadt de gerelateerde gebruiker en water en telt het aantal overtredingen.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // Zorg ervoor dat de gerelateerde modellen (user en water) worden geladen
        $rondes = ControleRonde::with(['user', 'water']) 
                                ->withCount('overtredingen')
                                ->latest()
                                ->get();

        return Inertia::render('ControleRondes/Index', [
            // DE SLEUTEL MOET 'rondes' ZIJN!
            'rondes' => $rondes, 
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // We hebben de lijst met beschikbare wateren nodig voor de dropdown
        $waters = Water::select('id', 'naam')->orderBy('naam')->get();

        return Inertia::render('ControleRondes/Start', [
            'waters' => $waters,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validatie
        $request->validate([
            'water_id' => 'required|exists:waters,id',
            'start_tijd' => 'nullable|date',
        ]);

        // 2. Ronde aanmaken
        $ronde = ControleRonde::create([
            'user_id' => auth()->id(), // De controller is de ingelogde gebruiker
            'water_id' => $request->water_id,
            'start_tijd' => $request->start_tijd ? $request->start_tijd : now(),      // Starttijd is het moment van opslaan
            'status' => 'Actief',       // De ronde is direct actief
        ]);

        // 3. De gebruiker doorsturen naar de gedetailleerde weergave van de gestarte ronde.
        // Dit is waar de overtredingen vastgelegd kunnen worden.
        return redirect()->route('controles.show', $ronde->id)
            ->with('message', 'Controle ronde op ' . $ronde->water->naam . ' is gestart.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ControleRonde $controle) // <-- HERNOEMD VAN $controleRonde NAAR $controle
    {
        // Gebruik nu $controle overal in de methode body
        $controle->load(['user', 'water', 'overtredingen.overtredingType']); 

        // 1. Haal de lijst met Overtreding Types op, inclusief de Foreign Key voor de standaard strafmaat.
        $overtredingTypes = OvertredingType::with('defaultStrafmaat', 'recidiveStrafmaat')
                                            ->orderBy('code')
                                            ->get();
        
        // 2. Haal de volledige lijst met Strafmaten op (omschrijvingen voor de dropdown).
        $strafmaten = Strafmaat::select('id', 'omschrijving')
                                ->orderBy('omschrijving')
                                ->get();
        
        return Inertia::render('ControleRondes/Show', [
            'ronde' => $controle, // Let op: $controle wordt als 'ronde' meegegeven aan Vue
            // Nodig voor het formulier om een overtreding toe te voegen
            'overtredingTypes' => $overtredingTypes,
            'strafmaten' => $strafmaten, // <--- OPLOSSING VOOR DE TYPEERROR: De lijst is nu beschikbaar in Vue.
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * Dit annuleert of verwijdert een gestarte controle definitief.
     */
    public function destroy(ControleRonde $controle)
    {
        // Verwijder eerst eventueel gerelateerde overtredingen (indien geen cascade in DB)
        $controle->overtredingen()->delete(); 

        // Verwijder de ronde zelf
        $controle->delete();

        // Stuur de gebruiker terug naar de indexpagina van de controles
        return redirect()->route('controles.index') // PAS DIT AAN NAAR JE JUISTE INDEX ROUTE NAAM
            ->with('success', 'Controle ronde succesvol geannuleerd.');
    }

    /**
     * CUSTOM ACTIE: Sluit een actieve ronde af, stelt de eindtijd vast en de status op 'Afgerond'.
     */
    public function afronden(Request $request, ControleRonde $controleRonde)
    {
        // 1. Validatie (alleen opmerkingen zijn optioneel)
        $request->validate([
            'opmerkingen' => 'nullable|string',
            'eind_tijd' => 'nullable|date',
        ]);

        // 2. Controleer of de ronde nog actief is
        if ($controleRonde->status !== 'Actief') {
            return redirect()->back()
                ->with('error', 'Deze ronde is al afgerond.');
        }

        // 3. De Ronde Afsluiten
        $controleRonde->update([
            'eind_tijd' => $request->eind_tijd ? $request->eind_tijd : now(), // Registreer het exacte moment van afsluiten
            'opmerkingen' => $request->opmerkingen,
            'status' => 'Afgerond', // Zet de status naar afgerond
        ]);

        // 4. Doorsturen naar het overzicht met succesbericht
        return redirect()->route('controles.index')
            ->with('message', 'Controle ronde op ' . $controleRonde->water->naam . ' succesvol afgerond.');
    }

    public function overtredingen()
    {
        // Dit is een hasMany relatie, en hoort thuis in het ControleRonde Model, niet in de Controller.
        // Mocht dit in het model staan, zorg dan dat het er zo uitziet:
        // return $this->hasMany(\App\Models\Overtreding::class, 'controle_ronde_id', 'id'); 
    }
}