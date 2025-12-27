<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Models\ControleRonde;
use App\Models\Overtreding;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        // 1. Filters ophalen
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $userId = $request->input('user_id');

        // 2. Basis Queries voor totalen
        // We bouwen queries op die we later uitvoeren, afhankelijk van filters
        $roundsQuery = ControleRonde::query();
        
        // Voor overtredingen moeten we joinen met rondes om op datum/user te filteren
        $violationsQuery = Overtreding::query()
            ->join('controle_rondes', 'overtredingen.controle_ronde_id', '=', 'controle_rondes.id');

        // Filters toepassen op basis queries
        if ($startDate) {
            $roundsQuery->whereDate('start_tijd', '>=', $startDate);
            $violationsQuery->whereDate('controle_rondes.start_tijd', '>=', $startDate);
        }
        if ($endDate) {
            $roundsQuery->whereDate('start_tijd', '<=', $endDate);
            $violationsQuery->whereDate('controle_rondes.start_tijd', '<=', $endDate);
        }
        if ($userId) {
            $roundsQuery->where('user_id', $userId);
            $violationsQuery->where('controle_rondes.user_id', $userId);
        }

        // 3. KPI Totalen berekenen
        $totalRounds = $roundsQuery->count();
        $totalViolations = $violationsQuery->count();
        // Aantal unieke controleurs die in deze periode actief waren
        $activeControllers = $roundsQuery->distinct('user_id')->count('user_id');


        // 4. Statistieken per Water (Top 10)
        $byWater = Overtreding::query()
            ->join('controle_rondes', 'overtredingen.controle_ronde_id', '=', 'controle_rondes.id')
            ->join('waters', 'controle_rondes.water_id', '=', 'waters.id')
            ->when($startDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '<=', $endDate))
            ->when($userId, fn($q) => $q->where('controle_rondes.user_id', $userId))
            ->select('waters.naam as name', DB::raw('count(*) as count'))
            ->groupBy('waters.naam')
            ->orderByDesc('count')
            ->limit(10)
            ->get();


        // 5. Statistieken per Type Overtreding (Top 10)
        $byType = Overtreding::query()
            ->join('controle_rondes', 'overtredingen.controle_ronde_id', '=', 'controle_rondes.id')
            ->join('overtreding_types', 'overtredingen.overtreding_type_id', '=', 'overtreding_types.id')
            ->when($startDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '<=', $endDate))
            ->when($userId, fn($q) => $q->where('controle_rondes.user_id', $userId))
            ->select('overtreding_types.code', 'overtreding_types.omschrijving as description', DB::raw('count(*) as count'))
            ->groupBy('overtreding_types.code', 'overtreding_types.omschrijving')
            ->orderByDesc('count')
            ->limit(10)
            ->get();


        // 6. Statistieken per Controleur (Tabel)
        // Eerst halen we de counts op per user_id om N+1 queries te voorkomen
        $roundsPerUser = ControleRonde::query()
            ->when($startDate, fn($q) => $q->whereDate('start_tijd', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('start_tijd', '<=', $endDate))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->select('user_id', DB::raw('count(*) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $violationsPerUser = Overtreding::query()
            ->join('controle_rondes', 'overtredingen.controle_ronde_id', '=', 'controle_rondes.id')
            ->when($startDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '<=', $endDate))
            ->when($userId, fn($q) => $q->where('controle_rondes.user_id', $userId))
            ->select('controle_rondes.user_id', DB::raw('count(*) as total'))
            ->groupBy('controle_rondes.user_id')
            ->pluck('total', 'user_id');

        // Haal relevante users op
        $usersQuery = User::whereIn('role', ['Controleur', 'Beheerder', 'Coördinator']);
        if ($userId) {
            $usersQuery->where('id', $userId);
        }
        $users = $usersQuery->orderBy('name')->get();

        // Map de data samen
        $byController = $users->map(function ($user) use ($roundsPerUser, $violationsPerUser) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'rounds' => $roundsPerUser[$user->id] ?? 0,
                'violations' => $violationsPerUser[$user->id] ?? 0,
            ];
        })->sortByDesc('rounds')->values();


        // 7. Recidive Gedrag (Top 10)
        // Zoek naar vispasnummers die meer dan 1x voorkomen in de gefilterde set
        $recidivism = Overtreding::query()
            ->join('controle_rondes', 'overtredingen.controle_ronde_id', '=', 'controle_rondes.id')
            ->whereNotNull('vispasnummer')
            ->where('vispasnummer', '!=', '')
            ->when($startDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '<=', $endDate))
            ->when($userId, fn($q) => $q->where('controle_rondes.user_id', $userId))
            ->select('vispasnummer', DB::raw('count(*) as count'), DB::raw('MAX(overtredingen.created_at) as last_violation_date'))
            ->groupBy('vispasnummer')
            ->having('count', '>', 1)
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'vispasnummer' => $item->vispasnummer,
                    'count' => $item->count,
                    'last_violation_date' => \Carbon\Carbon::parse($item->last_violation_date)->format('d-m-Y H:i'),
                ];
            });

        // 8. Statistieken per Maand (Grafiek)
        $byMonth = Overtreding::query()
            ->join('controle_rondes', 'overtredingen.controle_ronde_id', '=', 'controle_rondes.id')
            ->when($startDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '<=', $endDate))
            ->when($userId, fn($q) => $q->where('controle_rondes.user_id', $userId))
            ->select(
                DB::raw("DATE_FORMAT(controle_rondes.start_tijd, '%Y-%m') as month"),
                DB::raw('count(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => \Carbon\Carbon::createFromFormat('Y-m', $item->month)->translatedFormat('M Y'),
                    'count' => $item->count,
                ];
            });

        // 9. Lijst voor dropdown filter
        $usersList = User::orderBy('name')->select('id', 'name')->get();

        return Inertia::render('Beheer/Reports/Index', [
            'totals' => [
                'rounds' => $totalRounds,
                'violations' => $totalViolations,
                'active_controllers' => $activeControllers,
            ],
            'byWater' => $byWater,
            'byType' => $byType,
            'byController' => $byController,
            'byMonth' => $byMonth,
            'recidivism' => $recidivism,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'user_id' => $userId,
            ],
            'users' => $usersList,
        ]);
    }

    public function recidivist($vispasnummer)
    {
        // Haal alle overtredingen op voor dit vispasnummer, inclusief relaties
        $violations = Overtreding::with(['controleRonde.water', 'controleRonde.user', 'overtredingType'])
            ->where('vispasnummer', $vispasnummer)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'date' => $v->controleRonde->start_tijd,
                    'water' => $v->controleRonde->water->naam ?? 'Onbekend',
                    'controller' => $v->controleRonde->user->name ?? 'Onbekend',
                    'type' => ($v->overtredingType->code ?? '') . ' - ' . ($v->overtredingType->omschrijving ?? ''),
                    'measure' => $v->genomen_maatregel,
                    'details' => $v->details,
                ];
            });

        return Inertia::render('Beheer/Reports/Recidivist', [
            'vispasnummer' => $vispasnummer,
            'violations' => $violations,
        ]);
    }

    public function downloadRecidivistPdf($vispasnummer)
    {
        // Haal data op (dezelfde query als hierboven, maar we houden de Eloquent models voor de Blade view)
        $violations = Overtreding::with(['controleRonde.water', 'controleRonde.user', 'overtredingType'])
            ->where('vispasnummer', $vispasnummer)
            ->orderByDesc('created_at')
            ->get();

        $pdf = Pdf::loadView('pdf.recidivist', [
            'vispasnummer' => $vispasnummer,
            'violations' => $violations,
            'generated_at' => now()->format('d-m-Y H:i'),
        ]);

        return $pdf->download("Dossier_{$vispasnummer}.pdf");
    }
}