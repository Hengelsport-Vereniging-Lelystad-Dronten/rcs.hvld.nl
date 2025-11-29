<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Water;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaterApiController extends Controller
{
    /**
     * Zoek het dichtstbijzijnde water op basis van GPS-coördinaten (Haversine-formule).
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nearest(Request $request)
    {
        // 1. Validatie van de invoercoördinaten
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $userLat = $request->lat;
        $userLng = $request->lng;
        // Aarde straal in meters, essentieel voor Haversine
        $earthRadius = 6371000; 

        // 2. Haversine Selectie en Berekening
        // Let op: 'geometry_geojson' wordt HIER NIET geselecteerd.
        $wateren = Water::select('id', 'naam', 'latitude', 'longitude')
            ->selectRaw("
                ( {$earthRadius} * acos(
                    cos(radians(?))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians(?))
                    + sin(radians(?))
                    * sin(radians(latitude))
                ) ) AS distance_meters
            ", [$userLat, $userLng, $userLat])
            
            // Zorg ervoor dat we alleen wateren meenemen die coördinaten hebben
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            
            // Sorteer op de berekende afstand
            ->orderBy('distance_meters', 'asc')
            
            // Beperk tot het dichtstbijzijnde water
            ->limit(1)
            ->get();

        // 3. Response
        if ($wateren->isEmpty()) {
            return response()->json([
                'message' => 'Geen wateren gevonden met beschikbare coördinaten in de database.'
            ], 404);
        }

        // Return het dichtstbijzijnde water inclusief de afstand in meters
        return response()->json($wateren->first());
    }
}