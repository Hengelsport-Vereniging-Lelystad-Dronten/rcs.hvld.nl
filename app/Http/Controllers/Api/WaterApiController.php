<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Water;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaterApiController extends Controller
{
    /**
     * API Controller: WaterApiController
     *
     * Biedt eenvoudige API endpoints gerelateerd aan wateren.
     * Bijvoorbeeld het opzoeken van het dichtstbijzijnde water op basis van GPS-coördinaten.
     */
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

        $userLat = (float)$request->lat;
        $userLng = (float)$request->lng;

        // STAP 1: Controleer of de gebruiker zich BINNEN een polygoon bevindt.
        // We halen alle wateren op die een boundary hebben.
        $watersWithBoundary = Water::whereNotNull('boundary')->get();

        foreach ($watersWithBoundary as $water) {
            // De boundary wordt automatisch gecast naar array door het Model, of we decoden handmatig
            $boundary = is_string($water->boundary) ? json_decode($water->boundary, true) : $water->boundary;

            if ($this->isPointInPolygon($userLng, $userLat, $boundary)) {
                return response()->json([
                    'id' => $water->id,
                    'naam' => $water->naam,
                    'latitude' => $water->latitude,
                    'longitude' => $water->longitude,
                    'distance_meters' => 0, // Afstand is 0 want we staan erin
                    'match_type' => 'inside_polygon'
                ]);
            }
        }

        // STAP 2: Als we niet binnen een polygoon zijn, gebruik de Haversine formule
        // om het dichtstbijzijnde middelpunt te vinden (fallback).
        
        // Aarde straal in meters, essentieel voor Haversine
        $earthRadius = 6371000; 

        // Haversine Selectie en Berekening
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

    /**
     * Controleert of een punt (lng, lat) binnen een GeoJSON Polygon valt.
     * Gebruikt het Ray-Casting algoritme.
     */
    private function isPointInPolygon($lng, $lat, $geoJson)
    {
        // Basisvalidatie voor GeoJSON Polygon
        if (!is_array($geoJson) || !isset($geoJson['type']) || $geoJson['type'] !== 'Polygon' || empty($geoJson['coordinates'])) {
            return false;
        }

        // GeoJSON coördinaten zijn [lng, lat]. coordinates[0] is de buitenste ring.
        $polygon = $geoJson['coordinates'][0];
        $inside = false;
        $count = count($polygon);
        
        // Loop door alle vertices van de polygoon
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $vertI = $polygon[$i];
            $vertJ = $polygon[$j];
            
            // Ray-casting logica
            if ((($vertI[1] > $lat) != ($vertJ[1] > $lat)) &&
                ($lng < ($vertJ[0] - $vertI[0]) * ($lat - $vertI[1]) / ($vertJ[1] - $vertI[1]) + $vertI[0])) {
                $inside = !$inside;
            }
        }
        
        return $inside;
    }
}