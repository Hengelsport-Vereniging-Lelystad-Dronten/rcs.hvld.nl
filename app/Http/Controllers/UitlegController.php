<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\OvertredingType;
use App\Models\Water;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UitlegController extends Controller
{
    /**
     * Hoofdpagina van de Uitleg-sectie.
     */
    public function index()
    {
        return Inertia::render('Uitleg/Index');
    }

    /**
     * Toont de Waterkaart (bestaande functionaliteit).
     */
    public function kaart()
    {
        return Inertia::render('Uitleg/Kaart', [
            'waters' => Water::all(),
        ]);
    }

    /**
     * Toont de FAQ pagina met veelgestelde vragen.
     */
    public function faq()
    {
        $faqs = Faq::orderBy('order')->get();

        return Inertia::render('Uitleg/Faq', [
            'faqs' => $faqs
        ]);
    }

    /**
     * Toont een read-only overzicht van overtredingen en maatregelen.
     */
    public function overtredingen()
    {
        $types = OvertredingType::with(['defaultStrafmaat', 'recidiveStrafmaat'])
            ->orderBy('code')
            ->get();

        return Inertia::render('Uitleg/Overtredingen', [
            'types' => $types
        ]);
    }

    /**
     * Toont de pagina met handleidingen en externe links.
     */
    public function handleidingen()
    {
        return Inertia::render('Uitleg/Handleidingen');
    }
}