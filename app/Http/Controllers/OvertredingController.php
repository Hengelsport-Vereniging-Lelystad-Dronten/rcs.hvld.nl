<?php

namespace App\Http\Controllers;

use App\Models\ControleRonde;
use App\Models\Overtreding;
use Illuminate\Http\Request;

class OvertredingController extends Controller
{
    /**
     * Store a newly created overtreding in storage, gekoppeld aan een actieve ronde.
     */
    public function store(Request $request)
    {
        // 1. Validatie van de input
        $validated = $request->validate([
            'controle_ronde_id' => 'required|exists:controle_rondes,id', // Zorgt voor koppeling
            'overtreding_type_id' => 'required|exists:overtreding_types,id',
            'vispasnummer' => 'nullable|string|max:50',
            'genomen_maatregel' => 'required|string|max:255',
            'details' => 'nullable|string',
        ]);

        // 2. Controleer of de ronde actief is
        $ronde = ControleRonde::findOrFail($validated['controle_ronde_id']);
        
        if ($ronde->status !== 'Actief') {
            // Voorkom registratie van overtredingen in een gesloten ronde
            return redirect()->back()
                ->with('error', 'Overtredingen kunnen alleen worden toegevoegd aan een actieve ronde.');
        }

        // 3. Overtreding aanmaken
        Overtreding::create($validated);

        // 4. Terugsturen naar de ronde-pagina met een succesbericht
        return redirect()->route('controles.show', $ronde->id)
            ->with('message', 'Overtreding succesvol geregistreerd.');
    }

    // Voor deze app hebben we geen index, show, edit, update of destroy nodig, 
    // aangezien overtredingen genest zijn binnen ControleRonde.
}