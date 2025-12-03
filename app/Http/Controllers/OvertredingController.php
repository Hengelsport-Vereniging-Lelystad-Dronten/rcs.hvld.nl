<?php

namespace App\Http\Controllers;

use App\Models\ControleRonde;
use App\Models\Overtreding;
use Illuminate\Http\Request;

use App\Models\OvertredingType;

class OvertredingController extends Controller
{
    /**
     * Store a newly created overtreding in storage, gekoppeld aan een actieve ronde.
     */
    public function store(Request $request)
    {
        // 1. Validatie van de input
        $validated = $request->validate([
            'controle_ronde_id' => 'required|exists:controle_rondes,id',
            'overtreding_type_id' => 'required|exists:overtreding_types,id',
            'vispasnummer' => 'nullable|string|max:50',
            'details' => 'nullable|string',
        ]);

        // 2. Controleer of de ronde actief is
        $ronde = ControleRonde::findOrFail($validated['controle_ronde_id']);
        
        if ($ronde->status !== 'Actief') {
            return redirect()->back()
                ->with('error', 'Overtredingen kunnen alleen worden toegevoegd aan een actieve ronde.');
        }

        // 3. Bepaal de te nemen maatregel op basis van recidive
        $vispasnummer = $validated['vispasnummer'] ?? null;
        $overtredingTypeId = $validated['overtreding_type_id'];

        $offenseCount = 0;
        if ($vispasnummer) {
            $offenseCount = Overtreding::where('vispasnummer', $vispasnummer)
                                       ->where('overtreding_type_id', $overtredingTypeId)
                                       ->count();
        }

        $overtredingType = OvertredingType::with('defaultStrafmaat', 'recidiveStrafmaat')->find($overtredingTypeId);
        $genomenMaatregel = '';

        if ($offenseCount === 0) {
            // Eerste overtreding van dit type
            $genomenMaatregel = $overtredingType->defaultStrafmaat->omschrijving ?? 'Standaard maatregel niet gevonden';
        } elseif ($offenseCount === 1) {
            // Tweede overtreding (recidive)
            $genomenMaatregel = $overtredingType->recidiveStrafmaat->omschrijving ?? 'Recidive maatregel niet gevonden';
        } else {
            // Derde of latere overtreding
            $genomenMaatregel = 'justitie';
        }

        // 4. Overtreding aanmaken met de vastgestelde maatregel
        $overtredingData = $validated;
        $overtredingData['genomen_maatregel'] = $genomenMaatregel;

        Overtreding::create($overtredingData);

        // 5. Terugsturen naar de ronde-pagina met een succesbericht
        return redirect()->route('controles.show', $ronde->id)
            ->with('message', 'Overtreding succesvol geregistreerd.');
    }

    // Voor deze app hebben we geen index, show, edit, update of destroy nodig, 
    // aangezien overtredingen genest zijn binnen ControleRonde.
}