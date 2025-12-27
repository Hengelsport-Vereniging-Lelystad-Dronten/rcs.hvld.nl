<?php

namespace App\Http\Controllers;

use App\Models\Water;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KaartController extends Controller
{
    public function index()
    {
        // Haal alle wateren op die coördinaten hebben
        // We selecteren alleen de kolommen die we nodig hebben voor de kaart
        $waters = Water::whereNotNull('latitude')
                       ->whereNotNull('longitude')
                       ->select('id', 'naam', 'beschrijving', 'latitude', 'longitude')
                       ->get();

        // Render de Vue pagina 'Uitleg/Kaart' en geef de waters mee als prop
        return Inertia::render('Uitleg/Kaart', [
            'waters' => $waters
        ]);
    }
}
