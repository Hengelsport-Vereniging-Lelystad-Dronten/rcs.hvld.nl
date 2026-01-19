<?php

namespace App\Http\Controllers;

use App\Models\Water;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VisplannerController extends Controller
{
    /**
     * Toon de publieke waterkaart.
     * Deze pagina is toegankelijk zonder authenticatie.
     */
    public function index()
    {
        // Haal alle wateren op, inclusief gekoppelde nachtviszones.
        $waters = Water::with('nachtviszones')->get()->map(function ($water) {
            return [
                'id' => $water->id,
                'naam' => $water->naam,
                'beschrijving' => $water->beschrijving,
                'boundary' => $water->boundary,
                'beheersgebied' => $water->beheersgebied,
                'is_verboden' => $water->is_verboden,
                'nachtviszones' => $water->nachtviszones,
            ];
        });

        return Inertia::render('Visplanner/Index', [
            'waters' => $waters,
        ]);
    }
}