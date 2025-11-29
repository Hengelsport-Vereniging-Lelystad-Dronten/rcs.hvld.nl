<?php

namespace App\Http\Controllers;

use App\Models\ControleRonde;
use App\Models\Overtreding;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB; // Nodig voor statistieken

class DashboardController extends Controller
{
    /**
     * Toon het overzicht/dashboard voor coördinatoren en beheerders.
     */
    public function index()
    {
        // 1. KPI's ophalen
        $totalOvertredingen = Overtreding::count();
        $activeRondes = ControleRonde::where('status', 'Actief')->count();
        $totalControleuren = User::where('actief', true)->count();

        // 2. Meest voorkomende overtreding (Top 1)
        $topOvertreding = Overtreding::select('overtreding_type_id', DB::raw('count(*) as count'))
            ->groupBy('overtreding_type_id')
            ->orderByDesc('count')
            ->with('overtredingType') // Laad de relatie om de omschrijving te krijgen
            ->first();
            
        // 3. Meest gecontroleerde water (Top 1)
        $topWater = ControleRonde::select('water_id', DB::raw('count(*) as count'))
            ->groupBy('water_id')
            ->orderByDesc('count')
            ->with('water') // Laad de relatie om de naam te krijgen
            ->first();

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalOvertredingen' => $totalOvertredingen,
                'activeRondes' => $activeRondes,
                'totalControleuren' => $totalControleuren,
                'topOvertreding' => $topOvertreding ? [
                    'omschrijving' => $topOvertreding->overtredingType->omschrijving,
                    'count' => $topOvertreding->count,
                ] : null,
                'topWater' => $topWater ? [
                    'naam' => $topWater->water->naam,
                    'count' => $topWater->count,
                ] : null,
            ],
            // Optioneel: Laatste 5 afgeronde rondes
            'recentRondes' => ControleRonde::where('status', 'Afgerond')
                ->with(['user:id,name', 'water:id,naam'])
                ->orderByDesc('eind_tijd')
                ->limit(5)
                ->get()
        ]);
    }
}