<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RecidiveCheckService;
use App\Models\OvertredingType; // Nodig om de code op te zoeken uit het ID
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;


/**
 * RecidiveController
 * Handelt API-aanvragen af voor het controleren van recidive, waarbij het VISpasnummer
 * als identificatie voor de visser wordt gebruikt.
 *
 * BELANGRIJKE FIX: De controller is nu aangepast om 'overtreding_type_id' (integer ID)
 * te accepteren van de client, waarna de bijbehorende 'code' wordt opgezocht. Deze
 * 'code' wordt vervolgens gebruikt voor de aanroep naar de RecidiveCheckService.
 */
class RecidiveController extends Controller
{
    protected $recidiveCheckService;

    /**
     * Constructor voor het injecteren van de RecidiveCheckService.
     * De service wordt automatisch door Laravel's Service Container geïnjecteerd.
     *
     * @param RecidiveCheckService $recidiveCheckService De service met de recidive-logica.
     */
    public function __construct(RecidiveCheckService $recidiveCheckService)
    {
        $this->recidiveCheckService = $recidiveCheckService;
    }

    /**
     * Controleert het aantal eerdere overtredingen (recidives) voor een visser d.m.v. VISpasnummer.
     *
     * Vereiste parameters in de request body:
     * - vispasnummer (string): Het unieke nummer van de visser.
     * - overtreding_type_id (integer): Het ID van de overtredingstype, gebruikt om de code te vinden.
     * Optionele parameter:
     * - lookback_years (integer): Het aantal jaren terug om te controleren (standaard 2).
     *
     * @param Request $request Het HTTP-verzoek.
     * @return \Illuminate\Http\JsonResponse De JSON-response met de recidive-telling en strafmaatadvies.
     */
    public function check(Request $request): JsonResponse
    {
        // 1. Valideer de inkomende gegevens
        $validated = $request->validate([
            'vispasnummer' => 'required|string|max:20', 
            // FIX: Valideer nu correct op een bestaande INTEGER ID in de overtreding_types tabel
            'overtreding_type_id' => 'required|integer|exists:overtreding_types,id',
            'lookback_years' => 'sometimes|integer|min:1|max:5',
        ]);

        try {
            // Zoek het OvertredingType op om de 'code' te krijgen die de RecidiveCheckService vereist
            $overtredingType = OvertredingType::select('code')->find($validated['overtreding_type_id']);

            if (!$overtredingType) {
                // Hoort niet te gebeuren i.v.m. 'exists' validatie, maar is een noodoplossing.
                 throw new \Exception("Overtredingstype code kon niet worden opgezocht voor ID: {$validated['overtreding_type_id']}");
            }
            
            $overtredingCode = $overtredingType->code;
            
            // Converteer de optionele lookback_years naar lookbackMonths (service verwacht maanden).
            $lookbackYears = $validated['lookback_years'] ?? 1;
            $lookbackMonths = $lookbackYears * 12;

            // 2. Roep de service aan met de correcte methode en de opgezochte CODE
            // Dit lost de Type Hinting/Logic fout in de service aanroep op.
            $adviesResult = $this->recidiveCheckService->checkStatus(
                $validated['vispasnummer'],
                $overtredingCode, // Gebruik de opgezochte code (string)
                $lookbackMonths
            );

            // 3. Retourneer het resultaat in een gestructureerde JSON-response.
            return response()->json([
                'success' => true,
                'vispasnummer' => $validated['vispasnummer'],
                'overtreding_type_id' => $validated['overtreding_type_id'], 
                'overtreding_code' => $overtredingCode, // Voor inzicht, de interne code toevoegen
                'lookback_periode_maanden' => $lookbackMonths,
                
                // Gestructureerde resultaten van de Service
                'advies' => $adviesResult['bericht'],
                'is_recidivist' => $adviesResult['is_recidivist'],
                'historie_count' => $adviesResult['historie_count'],
                'geadviseerde_strafmaat_id' => $adviesResult['geadviseerde_strafmaat_id'],

            ]);

        } catch (\Exception $e) {
            // 4. Afhandeling van fouten
            Log::error("RecidiveController fout bij controle:", [
                'error_message' => $e->getMessage(), 
                'vispas' => $validated['vispasnummer'] ?? 'onbekend',
                'trace' => $e->getTraceAsString()
            ]);
            
            // Retourneer een 500-statuscode met een duidelijke foutmelding.
            return response()->json([
                'success' => false,
                'message' => 'Fout bij het uitvoeren van de recidivecontrole. Controleer de ingevoerde gegevens en de overtredingscode.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}