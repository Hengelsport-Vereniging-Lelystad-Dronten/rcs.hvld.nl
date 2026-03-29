<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User; 
use App\Models\Water; // Voorbereiding voor wateren
use App\Models\Overtreding;
use App\Models\OvertredingType;
use App\Models\Export;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        // Haal aantal niet-geëxporteerde actieve overtredingen op per export_status
        $nietGeexporteerdCount = Overtreding::actief()->voorExport()->count();
        $geexporteerdCount = Overtreding::actief()->geexporteerd()->count();
        $nietExporterenCount = Overtreding::actief()->exportStatus('niet_exporteren')->count();

        // Haal alle overtreding types op voor de filter dropdown
        $overtredingTypes = OvertredingType::orderBy('code')->get(['id', 'code', 'omschrijving']);

        return Inertia::render('Beheer/ExportOvertredingen/Index', [
            'nietGeexporteerdCount' => $nietGeexporteerdCount,
            'geexporteerdCount' => $geexporteerdCount,
            'nietExporterenCount' => $nietExporterenCount,
            'overtredingTypes' => $overtredingTypes,
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Preview van overtredingen die geëxporteerd zouden worden op basis van filters.
     */
    public function exportOvertredingenPreview(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date', 'overtreding_type_id', 'export_status', 'force_re_export']);

        $query = Overtreding::actief()
            ->with(['overtredingType', 'controleRonde.user'])
            ->orderBy('geconstateerd_op');

        // Filters toepassen
        if (!empty($filters['start_date'])) {
            $query->whereDate('geconstateerd_op', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('geconstateerd_op', '<=', $filters['end_date']);
        }

        if (!empty($filters['overtreding_type_id'])) {
            $query->where('overtreding_type_id', $filters['overtreding_type_id']);
        }

        if (!empty($filters['export_status'])) {
            $query->exportStatus($filters['export_status']);
        } else {
            // Standaard alleen 'wel_exporteren' tonen
            $query->exportStatus('wel_exporteren');
        }

        // Filter meldingen zonder overtreding (type code 00) weg
        $query->whereHas('overtredingType', function ($query) {
            $query->where('code', '<>', '00');
        });

        // Alleen niet-geëxporteerde tenzij force re-export of als we alle statussen tonen
        if (empty($filters['force_re_export']) && $filters['export_status'] !== 'geexporteerd') {
            $query->whereNull('exported_at');
        }

        $overtredingen = $query->get();

        return response()->json([
            'overtredingen' => $overtredingen
        ]);
    }

    /**
     * Exporteer geselecteerde overtredingen naar PDF.
     */
    public function exportOvertredingenPdf(Request $request)
    {
        $selectedOvertredingen = $request->input('selected_overtredingen', []);
        $forceReExport = $request->boolean('force_re_export', false);

        if (empty($selectedOvertredingen)) {
            return back()->with('error', 'Geen overtredingen geselecteerd om te exporteren.');
        }

        $overtredingen = Overtreding::whereIn('id', $selectedOvertredingen)
            ->actief()
            ->exportStatus('wel_exporteren')
            ->whereHas('overtredingType', function ($query) {
                $query->where('code', '<>', '00');
            })
            ->with(['overtredingType', 'controleRonde.user'])
            ->orderBy('geconstateerd_op')
            ->get();

        if ($overtredingen->isEmpty()) {
            return back()->with('error', 'Geen geldige overtredingen gevonden om te exporteren.');
        }

        // Genereer PDF
        $pdf = Pdf::loadView('pdf.overtredingen-export', [
            'overtredingen' => $overtredingen,
            'generated_at' => now()->format('d-m-Y H:i'),
            'generated_by' => auth()->user()->name,
            'force_re_export' => $forceReExport,
        ]);

        // Sla PDF op in storage
        $filename = 'overtredingen_export_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filePath = 'exports/' . $filename;
        Storage::put($filePath, $pdf->output());

        // Maak export record aan
        $export = Export::create([
            'filename' => $filename,
            'original_filename' => $filename,
            'file_path' => $filePath,
            'export_type' => 'overtredingen',
            'record_count' => $overtredingen->count(),
            'filters' => $request->only(['start_date', 'end_date', 'overtreding_type_id', 'export_status', 'force_re_export']),
            'selected_records' => $selectedOvertredingen,
            'created_by' => auth()->id(),
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
                    'selected_overtredingen' => $selectedOvertredingen,
                    'export_id' => $export->id,
                    'force_re_export' => false,
                ])
                ->log('Overtredingen geëxporteerd naar PDF');
        }

        // Redirect naar export overzicht met succes bericht
        return redirect()->route('beheer.exports.index')->with('success', 'Export succesvol aangemaakt: ' . $filename);
    }

    /**
     * Update de export status van een overtreding.
     */
    public function updateExportStatus(Request $request, Overtreding $overtreding)
    {
        $request->validate([
            'export_status' => 'required|in:wel_exporteren,niet_exporteren,geexporteerd',
        ]);

        $oldStatus = $overtreding->export_status;
        $overtreding->update(['export_status' => $request->export_status]);

        // Log de status wijziging
        activity()
            ->performedOn($overtreding)
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $request->export_status,
            ])
            ->log('Export status overtreding gewijzigd');

        return response()->json(['success' => true]);
    }

    /**
     * Toon overzicht van alle exports.
     */
    public function exportsIndex()
    {
        $exports = Export::with('creator')
            ->ofType('overtredingen')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Beheer/Exports/Index', [
            'exports' => $exports,
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Download een export bestand.
     */
    public function downloadExport(Export $export)
    {
        // Check of bestand bestaat
        if (!$export->fileExists()) {
            return back()->with('error', 'Export bestand niet gevonden.');
        }

        // Markeer als gedownload
        $export->markAsDownloaded();

        // Log de download
        activity()
            ->performedOn($export)
            ->log('Export gedownload');

        // Return file download
        return Storage::download($export->file_path, $export->original_filename);
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
