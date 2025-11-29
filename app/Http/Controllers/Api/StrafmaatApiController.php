<?php

namespace App\Http\Controllers\Api;

use App\Models\Strafmaat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller voor het beheren van de API-endpoints voor strafmaten.
 * Dit is de backend die de data uit MariaDB haalt en als JSON aanbiedt.
 */
class StrafmaatController extends Controller
{
    /**
     * Haalt alle strafmaten op uit de database en retourneert deze als JSON.
     * Dit endpoint dient de GET-request van de Vue-frontend.
     *
     * @param Request $request De inkomende HTTP-request.
     * @return JsonResponse De JSON-respons met de strafmaten.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 1. Haal de strafmaten op uit de MariaDB via het Eloquent Model.
            // Sorteer direct op 'code' voor de Vue-frontend.
            $strafmaten = Strafmaat::select('id', 'code', 'omschrijving', 'datum')
                ->orderBy('code')
                ->get();
            
            // 2. Transformatie van de data.
            // We transformeren de collectie naar een array om ervoor te zorgen dat 
            // 'id' als string wordt geretourneerd (consistentie met Firebase / frontend-keys).
            $formattedData = $strafmaten->map(function ($item) {
                return [
                    // Zorg ervoor dat de ID als string wordt geretourneerd
                    'id' => (string)$item->id, 
                    'code' => $item->code,
                    'omschrijving' => $item->omschrijving,
                    // De 'datum' kan null zijn in de database, dit wordt correct afgehandeld.
                    'datum' => $item->datum, 
                ];
            });

            Log::info('Strafmaten succesvol opgehaald.', ['count' => $strafmaten->count()]);

            // 3. Retourneer de geformatteerde data als een JSON-respons.
            // Laravel zorgt automatisch voor de 'Content-Type: application/json' header.
            return response()->json($formattedData);

        } catch (\Exception $e) {
            // Log de fout voor debugging op de server
            Log::error('Fout bij ophalen strafmaten data: ' . $e->getMessage(), ['exception' => $e]);
            
            // Stuur een consistente foutmelding terug naar de frontend met HTTP 500 status.
            return response()->json([
                'success' => false,
                'message' => 'Er is een serverfout opgetreden bij het laden van de strafmaten.'
            ], 500);
        }
    }
}