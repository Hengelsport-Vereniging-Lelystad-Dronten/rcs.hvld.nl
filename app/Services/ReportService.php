<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Overtreding;
use App\Models\ControleRonde;
use App\Models\OvertredingType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * app/Services/ReportService.php
 *
 * Service voor het genereren van periodieke rapportages.
 * Aggregeert overtredingen, rondes en statistieken voor gegeven periodes.
 */
class ReportService
{
    /**
     * Genereer een rapport voor een gegeven periode en type.
     */
    public function generateReport(string $reportType, Carbon $startDate, Carbon $endDate): Report
    {
        $dataSummary = $this->aggregateData($startDate, $endDate);

        $report = Report::create([
            'report_type' => $reportType,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'data_summary' => $dataSummary,
            'generated_at' => now(),
            'created_by' => auth()->check() ? auth()->id() : null,
        ]);

        return $report;
    }

    /**
     * Aggregeer alle relevante statistieken voor een gegeven periode.
     */
    private function aggregateData(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_overtredingen' => $this->getTotalOvertredingen($startDate, $endDate),
            'total_rondes' => $this->getTotalRondes($startDate, $endDate),
            'top_overtredingTypes' => $this->getTopOvertredingTypes($startDate, $endDate),
            'top_controleurs' => $this->getTopControleurs($startDate, $endDate),
            'top_wateren' => $this->getTopWateren($startDate, $endDate),
            'maatregel_breakdown' => $this->getMaatregelBreakdown($startDate, $endDate),
            'recidive_count' => $this->getRecidiveCount($startDate, $endDate),
            'vispas_ingenomen_count' => $this->getVispasIngenomenCount($startDate, $endDate),
        ];
    }

    /**
     * Totaal aantal overtredingen in periode.
     */
    private function getTotalOvertredingen(Carbon $startDate, Carbon $endDate): int
    {
        return Overtreding::whereBetween('created_at', [$startDate, $endDate])->count();
    }

    /**
     * Totaal aantal afgeronde controlerondes in periode.
     */
    private function getTotalRondes(Carbon $startDate, Carbon $endDate): int
    {
        return ControleRonde::whereBetween('eind_tijd', [$startDate, $endDate])
            ->where('status', 'afgerond')
            ->count();
    }

    /**
     * Top 5 meest voorkomende overtredingstypen.
     */
    private function getTopOvertredingTypes(Carbon $startDate, Carbon $endDate): array
    {
        return Overtreding::whereBetween('overtredingen.created_at', [$startDate, $endDate])
            ->join('overtreding_types', 'overtredingen.overtreding_type_id', '=', 'overtreding_types.id')
            ->select('overtreding_types.code', 'overtreding_types.omschrijving', DB::raw('count(*) as count'))
            ->groupBy('overtreding_types.id', 'overtreding_types.code', 'overtreding_types.omschrijving')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Top 5 meest actieve controleurs.
     */
    private function getTopControleurs(Carbon $startDate, Carbon $endDate): array
    {
        return ControleRonde::whereBetween('controle_rondes.eind_tijd', [$startDate, $endDate])
            ->where('controle_rondes.status', 'afgerond')
            ->join('users', 'controle_rondes.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', DB::raw('count(*) as rondes_count'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('rondes_count')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Top 5 meest gecontroleerde wateren.
     */
    private function getTopWateren(Carbon $startDate, Carbon $endDate): array
    {
        return ControleRonde::whereBetween('controle_rondes.eind_tijd', [$startDate, $endDate])
            ->where('controle_rondes.status', 'afgerond')
            ->join('waters', 'controle_rondes.water_id', '=', 'waters.id')
            ->select('waters.id', 'waters.naam', DB::raw('count(*) as control_count'))
            ->groupBy('waters.id', 'waters.naam')
            ->orderByDesc('control_count')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Uitsplitsing van genomen maatregelen.
     */
    private function getMaatregelBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        return Overtreding::whereBetween('overtredingen.created_at', [$startDate, $endDate])
            ->select('genomen_maatregel', DB::raw('count(*) as count'))
            ->groupBy('genomen_maatregel')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * Aantal recidivegevallen (overtredingen op dezelfde VISpasnummer > 1).
     */
    private function getRecidiveCount(Carbon $startDate, Carbon $endDate): int
    {
        return Overtreding::whereBetween('overtredingen.created_at', [$startDate, $endDate])
            ->whereNotNull('vispasnummer')
            ->select('vispasnummer', DB::raw('count(*) as occ'))
            ->groupBy('vispasnummer')
            ->having('occ', '>', 1)
            ->count();
    }

    /**
     * Aantal ingename VISpassen.
     */
    private function getVispasIngenomenCount(Carbon $startDate, Carbon $endDate): int
    {
        return Overtreding::whereBetween('created_at', [$startDate, $endDate])
            ->where('vispas_ingenomen', true)
            ->count();
    }

    /**
     * Bepaal start en eind van week/maand/kwartaal op basis van huidige datum.
     */
    public static function getPeriodDates(string $reportType, ?Carbon $forDate = null): array
    {
        $date = $forDate ?? now();

        return match($reportType) {
            'weekly' => [
                'start' => $date->copy()->startOfWeek(),
                'end' => $date->copy()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
            ],
            'quarterly' => [
                'start' => $date->copy()->startOfQuarter(),
                'end' => $date->copy()->endOfQuarter(),
            ],
            default => [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ],
        };
    }
}
