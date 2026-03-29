<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User; 
use App\Models\Water; // Voorbereiding voor wateren
use App\Models\Overtreding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class BeheerController extends Controller
{
    /**
     * Controller: BeheerController
     *
     * Algemene beheerpagina's en hulpfuncties voor gebruikers met beheerrechten.
     * Deze controller bevat vaak overzicht- en dashboardmethods voor beheerders.
     */
    /**
     * Toon de hoofd Beheerder Dashboard pagina.
     */
    public function index()
    {
        return Inertia::render('Beheer/Index');
    }

    /**
     * Toon de Gebruikers Beheer pagina (Overzicht).
     */
    public function usersIndex()
    {
        // Haal alle gebruikers op, gesorteerd op rol en naam
        $users = User::orderByRaw("FIELD(role, 'Beheerder', 'Coordinator', 'Controleur')")->get();
        
        return Inertia::render('Beheer/Users/Index', [
            'users' => $users
        ]);
    }

    /**
     * Toon de Wateren Beheer pagina (Overzicht).
     */
    public function watersIndex()
    {
        // Haal alle wateren op
        $waters = Water::orderBy('naam')->get();
        
        return Inertia::render('Beheer/Waters/Index', [
            'waters' => $waters
        ]);
    }

    /**
     * Toon de Export Overtredingen pagina.
     */
    public function exportOvertredingenIndex()
    {
        // Haal aantal niet-geëxporteerde actieve overtredingen op
        $nietGeexporteerdCount = Overtreding::actief()->nietGeexporteerd()->count();
        $geexporteerdCount = Overtreding::actief()->geexporteerd()->count();

        return Inertia::render('Beheer/ExportOvertredingen/Index', [
            'nietGeexporteerdCount' => $nietGeexporteerdCount,
            'geexporteerdCount' => $geexporteerdCount,
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Exporteer actieve niet-geëxporteerde overtredingen naar PDF.
     */
    public function exportOvertredingenPdf(Request $request)
    {
        $forceReExport = $request->boolean('force_re_export', false);

        // Haal overtredingen op
        $query = Overtreding::actief()
            ->with(['overtredingType', 'controleRonde.user'])
            ->orderBy('geconstateerd_op');

        if (!$forceReExport) {
            $query->nietGeexporteerd();
        }

        $overtredingen = $query->get();

        if ($overtredingen->isEmpty()) {
            return back()->with('error', 'Geen overtredingen gevonden om te exporteren.');
        }

        // Genereer PDF
        $pdf = Pdf::loadView('pdf.overtredingen-export', [
            'overtredingen' => $overtredingen,
            'generated_at' => now()->format('d-m-Y H:i'),
            'generated_by' => auth()->user()->name,
            'force_re_export' => $forceReExport,
        ]);

        // Markeer als geëxporteerd (indien niet force re-export)
        if (!$forceReExport) {
            DB::transaction(function () use ($overtredingen) {
                foreach ($overtredingen as $overtreding) {
                    $overtreding->update(['exported_at' => now()]);
                }
            });

            // Log de export
            activity()
                ->withProperties([
                    'export_type' => 'overtredingen_export',
                    'count' => $overtredingen->count(),
                    'force_re_export' => false,
                ])
                ->log('Overtredingen geëxporteerd naar PDF');
        }

        return $pdf->download('overtredingen_export_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }
    /**
     * Reset export status voor alle overtredingen (admin functie).
     */
    public function resetExportStatus(Request $request)
    {
        $count = Overtreding::whereNotNull('exported_at')->update(['exported_at' => null]);

        activity()
            ->withProperties([
                'action' => 'reset_export_status',
                'count' => $count,
            ])
            ->log('Export status gereset voor alle overtredingen');

        return back()->with('success', "Export status gereset voor {$count} overtredingen.");
    }
}
