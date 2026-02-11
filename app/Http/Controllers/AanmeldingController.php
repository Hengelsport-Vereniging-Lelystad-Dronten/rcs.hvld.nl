<?php

namespace App\Http\Controllers;

use App\Mail\NieuweAanmelding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class AanmeldingController extends Controller
{
    /**
     * Toon het aanmeldformulier.
     */
    public function create()
    {
        return Inertia::render('Aanmelden/Create');
    }

    /**
     * Verwerk de aanmelding en verstuur notificaties.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'voornaam' => 'required|string|max:255',
            'achternaam' => 'required|string|max:255',
            'geboortedatum' => 'required|date',
            'adres' => 'required|string|max:255',
            'postcode' => 'required|string|max:20',
            'woonplaats' => 'required|string|max:255',
            'telefoonnummer' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'lidnummer' => 'nullable|string|max:50',
            'akkoord_lidmaatschap' => 'accepted',
            'akkoord_vog' => 'accepted',
            'motivatie_tekst' => 'required|string',
            'motivatie_keuzes' => 'array',
            'ervaring_tekst' => 'nullable|string',
        ]);

        // E-mail ontvangers configureren
        // TODO: Verplaats specifieke e-mailadressen eventueel naar een config bestand of .env
        $recipients = [
            'controleurs@hvld.nl',
            // Voeg hier de e-mailadressen toe voor rroethof en Mathieu indien deze afwijken
            // 'rroethof@hvld.nl',
            // 'mathieu@hvld.nl'
        ];

        // Verstuur de e-mail
        Mail::to($recipients)->send(new NieuweAanmelding($validated));

        return redirect()->route('aanmelden.bedankt');
    }

    /**
     * Toon de bedankt pagina.
     */
    public function bedankt()
    {
        return Inertia::render('Aanmelden/Bedankt');
    }
}
