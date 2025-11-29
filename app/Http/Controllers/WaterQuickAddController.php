<?php

namespace App\Http\Controllers;

use App\Models\Water;
use Illuminate\Http\Request;

class WaterQuickAddController extends Controller
{
    /**
     * Store een nieuw water, specifiek voor snelle toevoeging door de controleur.
     */
    public function store(Request $request)
    {
        // 1. Validatie: Alleen de naam is verplicht
        $validated = $request->validate([
            'naam' => 'required|string|max:255|unique:waters,naam',
            'type' => 'nullable|string|max:50',
            'beheersgebied' => 'nullable|string|max:255',
            // detail_tekst niet vereist bij quick add
        ]);
        
        // Zorg ervoor dat de user_id van de aanmaker wordt opgeslagen (indien nodig),
        // of een default waarde wordt gebruikt. We gaan uit van minimale velden:
        
        // 2. Creëer het water
        $water = Water::create($validated);

        // 3. Stuur het nieuwe water object terug (nodig voor de dropdown in Vue)
        return response()->json([
            'water' => $water,
            // Flash message data wordt door Inertia opgevangen 
        ], 201);
    }
}