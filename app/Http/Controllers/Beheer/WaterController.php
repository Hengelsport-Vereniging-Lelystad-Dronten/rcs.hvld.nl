<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Models\Water;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule; // Nodig voor unique-validatie in update

class WaterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Alle wateren ophalen, gesorteerd op naam
        $waters = Water::orderBy('naam')->get();

        return Inertia::render('Beheer/Waters/Index', [
            'waters' => $waters
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // We gebruiken één Vue-component voor create en edit (CreateEdit.vue)
        return Inertia::render('Beheer/Waters/CreateEdit', [
            // Geef een leeg object mee zodat de Vue component weet dat het om 'create' gaat
            'water' => (object) [
                'id' => null,
                'naam' => '',
                'beschrijving' => '',
                'latitude' => null,
                'longitude' => null,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'naam' => 'required|string|max:255|unique:waters,naam',
            'beschrijving' => 'nullable|string',
            // GPS-velden toegevoegd, deze zijn vereist in de frontend
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // OPGELOST: Vervangen van ongedefinieerde sanitize_html() door strip_tags().
        // Dit voorkomt de fatal error.
        $validated['beschrijving'] = strip_tags($validated['beschrijving'] ?? '');

        // Gebruik alleen de gevalideerde data
        Water::create($validated);

        return redirect()->route('beheer.waters.index')
            ->with('message', 'Water "' . $validated['naam'] . '" succesvol toegevoegd.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Deze methode is leeg gelaten
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Zoek het water op basis van de meegegeven ID
        $water = Water::findOrFail($id);

        return Inertia::render('Beheer/Waters/CreateEdit', [
            'water' => $water, // Geef het water object mee aan de Vue-component
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $water = Water::findOrFail($id); // Eerst ophalen

        $validated = $request->validate([
            // Gebruik Rule::unique om de huidige record te negeren
            'naam' => ['required', 'string', 'max:255', Rule::unique('waters', 'naam')->ignore($water->id)],
            'beschrijving' => 'nullable|string',
            // GPS-velden toegevoegd
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // OPGELOST: Vervangen van ongedefinieerde sanitize_html() door strip_tags().
        // Dit voorkomt de fatal error.
        $validated['beschrijving'] = strip_tags($validated['beschrijving'] ?? '');

        // Gebruik alleen de gevalideerde data
        $water->update($validated);

        return redirect()->route('beheer.waters.index')
            ->with('message', 'Water "' . $water->naam . '" succesvol bijgewerkt.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $water = Water::findOrFail($id); // Eerst ophalen
        $water_naam = $water->naam; // Naam vastleggen voor de flash message
        
        $water->delete();

        return redirect()->route('beheer.waters.index')
            ->with('message', 'Water "' . $water_naam . '" succesvol verwijderd.');
    }
}