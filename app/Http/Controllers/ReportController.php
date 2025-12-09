<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * app/Http/Controllers/ReportController.php
 *
 * Controller voor het beheren van periodieke rapportages.
 * Handelt requests af voor generering, opzoeking en download van rapporten.
 */
class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Toon overzicht van alle gegenereerde rapporten (alleen Beheerders).
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isBeheerder(), 403);

        $reports = Report::orderByDesc('generated_at')
            ->paginate(15);

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
        ]);
    }

    /**
     * Toon gedetailleerde rapport pagina.
     */
    public function show(Report $report)
    {
        abort_unless(auth()->user()->isBeheerder(), 403);

        return Inertia::render('Reports/Show', [
            'report' => $report,
        ]);
    }

    /**
     * Genereer een nieuw rapport voor gegeven periode.
     */
    public function generate(Request $request)
    {
        abort_unless(auth()->user()->isBeheerder(), 403);

        $validated = $request->validate([
            'report_type' => 'required|in:weekly,monthly,quarterly,custom',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date',
        ]);

        $reportType = $validated['report_type'];
        
        // Bepaal start en eind datum
        if ($reportType === 'custom') {
            $startDate = Carbon::parse($validated['period_start'] ?? now());
            $endDate = Carbon::parse($validated['period_end'] ?? now());
        } else {
            $dates = ReportService::getPeriodDates($reportType);
            $startDate = $dates['start'];
            $endDate = $dates['end'];
        }

        // Controleer of rapport al bestaat voor deze periode
        $existing = Report::where('report_type', $reportType)
            ->where('period_start', $startDate)
            ->where('period_end', $endDate)
            ->first();

        if ($existing) {
            return redirect()->route('reports.show', $existing->id)
                ->with('message', 'Rapport voor deze periode bestaat al.');
        }

        // Genereer nieuwe rapport
        $report = $this->reportService->generateReport($reportType, $startDate, $endDate);

        return redirect()->route('reports.show', $report->id)
            ->with('message', 'Rapport succesvol gegenereerd.');
    }

    /**
     * Download rapport als JSON.
     */
    public function download(Report $report)
    {
        abort_unless(auth()->user()->isBeheerder(), 403);

        $filename = "rapport_{$report->report_type}_{$report->period_start->format('Y-m-d')}.json";

        return response()->json($report->data_summary, 200, [
            'Content-Disposition' => "attachment; filename=$filename",
        ]);
    }

    /**
     * Auto-genereer wekelijks rapport voor vorige week (via scheduled job).
     */
    public function generateWeekly()
    {
        $lastWeek = now()->subWeek();
        $dates = ReportService::getPeriodDates('weekly', $lastWeek);

        $existing = Report::where('report_type', 'weekly')
            ->where('period_start', $dates['start'])
            ->first();

        if (!$existing) {
            $this->reportService->generateReport('weekly', $dates['start'], $dates['end']);
        }
    }

    /**
     * Auto-genereer maandelijks rapport voor vorige maand (via scheduled job).
     */
    public function generateMonthly()
    {
        $lastMonth = now()->subMonth();
        $dates = ReportService::getPeriodDates('monthly', $lastMonth);

        $existing = Report::where('report_type', 'monthly')
            ->where('period_start', $dates['start'])
            ->first();

        if (!$existing) {
            $this->reportService->generateReport('monthly', $dates['start'], $dates['end']);
        }
    }

    /**
     * Auto-genereer kwartaalrapport voor vorig kwartaal (via scheduled job).
     */
    public function generateQuarterly()
    {
        $lastQuarter = now()->subQuarters(1);
        $dates = ReportService::getPeriodDates('quarterly', $lastQuarter);

        $existing = Report::where('report_type', 'quarterly')
            ->where('period_start', $dates['start'])
            ->first();

        if (!$existing) {
            $this->reportService->generateReport('quarterly', $dates['start'], $dates['end']);
        }
    }
}
