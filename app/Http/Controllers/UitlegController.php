<?php

namespace App\Http\Controllers;

use App\Models\Water;
use Inertia\Inertia;

class UitlegController extends Controller
{
    /**
     * Toont de interactieve waterkaart met polygonen.
     */
    public function kaart()
    {
        return Inertia::render('Uitleg/Kaart', [
            'waters' => Water::all(),
        ]);
    }
}