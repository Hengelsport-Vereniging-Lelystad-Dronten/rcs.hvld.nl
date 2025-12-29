<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Models\Water;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule; // Nodig voor unique-validatie in update

/**
 * Controller: WaterController
 *
 * Beheert het CRUD-proces voor 'Water' resources in het beheerderspaneel.
 * Verzorgt het aanmaken, bewerken, verwijderen en tonen van wateren voor de frontend.
 */
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
                'boundary' => null,
                'center_lat' => null,
                'center_lng' => null,
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
            'boundary' => 'nullable|json', // Accepteert zowel Polygon als MultiPolygon GeoJSON
            // GPS-velden toegevoegd, deze zijn vereist in de frontend
            'center_lat' => 'nullable|numeric',
            'center_lng' => 'nullable|numeric',
        ]);

        // OPMERKING: We gebruiken hier GEEN strip_tags() meer op de beschrijving.
        // Dit staat toe dat HTML uit de WYSIWYG-editor wordt opgeslagen in de database.

        // Mapping van frontend center_lat/lng naar database latitude/longitude
        if (isset($validated['center_lat'])) {
            $validated['latitude'] = $validated['center_lat'];
            unset($validated['center_lat']);
        }
        if (isset($validated['center_lng'])) {
            $validated['longitude'] = $validated['center_lng'];
            unset($validated['center_lng']);
        }

        // Gebruik alleen de gevalideerde data
        $water = Water::create($validated);

        activity()
            ->performedOn($water)
            ->log('Water aangemaakt');

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

        // Map database latitude/longitude naar frontend center_lat/center_lng
        $water->center_lat = $water->latitude;
        $water->center_lng = $water->longitude;

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
            'boundary' => 'nullable|json', // Accepteert zowel Polygon als MultiPolygon GeoJSON
            // GPS-velden toegevoegd
            'center_lat' => 'nullable|numeric',
            'center_lng' => 'nullable|numeric',
        ]);

        // OPMERKING: We gebruiken hier GEEN strip_tags() meer op de beschrijving.
        // Dit staat toe dat HTML uit de WYSIWYG-editor wordt opgeslagen in de database.

        // Mapping van frontend center_lat/lng naar database latitude/longitude
        if (isset($validated['center_lat'])) {
            $validated['latitude'] = $validated['center_lat'];
            unset($validated['center_lat']);
        }
        if (isset($validated['center_lng'])) {
            $validated['longitude'] = $validated['center_lng'];
            unset($validated['center_lng']);
        }

        $oldData = $water->only(['naam', 'beschrijving', 'latitude', 'longitude', 'boundary']);

        // Gebruik alleen de gevalideerde data
        $water->update($validated);

        activity()
            ->performedOn($water)
            ->withProperties(['old' => $oldData, 'new' => $validated])
            ->log('Water bijgewerkt');

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
        
        activity()
            ->performedOn($water)
            ->log('Water verwijderd');

        $water->delete();

        return redirect()->route('beheer.waters.index')
            ->with('message', 'Water "' . $water_naam . '" succesvol verwijderd.');
    }
}