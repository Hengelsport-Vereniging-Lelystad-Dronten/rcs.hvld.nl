<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ControleRonde;
use App\Models\Overtreding;
use App\Models\User;

class SendPeriodicReport extends Command
{
    /**
     * De naam en handtekening van het commando.
     * Voorbeeld gebruik: php artisan report:send last_week bestuur@hvld.nl
     */
    protected $signature = 'report:send {period : De periode (daily, yesterday, weekly, last_week, monthly, last_month)} {email : Het e-mailadres van de ontvanger}';

    /**
     * De beschrijving van het commando.
     */
    protected $description = 'Genereer en mail een periodieke rapportage PDF';

    /**
     * Voer het commando uit.
     */
    public function handle()
    {
        $period = $this->argument('period');
        $email = $this->argument('email');

        // 1. Periode bepalen
        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'daily':
            case 'today':
                $startDate = now()->format('Y-m-d');
                $endDate = now()->format('Y-m-d');
                break;
            case 'yesterday':
                $startDate = now()->subDay()->format('Y-m-d');
                $endDate = $startDate;
                break;
            case 'weekly':
            case 'this_week':
                $startDate = now()->startOfWeek()->format('Y-m-d');
                $endDate = now()->endOfWeek()->format('Y-m-d');
                break;
            case 'last_week':
                $startDate = now()->subWeek()->startOfWeek()->format('Y-m-d');
                $endDate = now()->subWeek()->endOfWeek()->format('Y-m-d');
                break;
            case 'monthly':
            case 'this_month':
                $startDate = now()->startOfMonth()->format('Y-m-d');
                $endDate = now()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $endDate = now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            default:
                $this->error("Ongeldige periode: $period. Gebruik: daily, yesterday, weekly, last_week, monthly, last_month.");
                return 1;
        }

        $this->info("Rapport genereren voor: $startDate t/m $endDate");

        // 2. Data verzamelen (Logica overgenomen van ReportsController)
        $roundsQuery = ControleRonde::query();
        $violationsQuery = Overtreding::query()->join('controle_rondes', 'overtredingen.controle_ronde_id', '=', 'controle_rondes.id');

        if ($startDate) {
            $roundsQuery->whereDate('start_tijd', '>=', $startDate);
            $violationsQuery->whereDate('controle_rondes.start_tijd', '>=', $startDate);
        }
        if ($endDate) {
            $roundsQuery->whereDate('start_tijd', '<=', $endDate);
            $violationsQuery->whereDate('controle_rondes.start_tijd', '<=', $endDate);
        }

        $totalRounds = $roundsQuery->count();
        $totalViolations = $violationsQuery->count();
        $activeControllers = $roundsQuery->distinct('user_id')->count('user_id');

        $byWater = Overtreding::query()
            ->join('controle_rondes', 'overtredingen.controle_ronde_id', '=', 'controle_rondes.id')
            ->join('waters', 'controle_rondes.water_id', '=', 'waters.id')
            ->when($startDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '<=', $endDate))
            ->select('waters.naam as name', DB::raw('count(*) as count'))
            ->groupBy('waters.naam')
            ->orderByDesc('count')
            ->limit(10)->get();

        $byType = Overtreding::query()
            ->join('controle_rondes', 'overtredingen.controle_ronde_id', '=', 'controle_rondes.id')
            ->join('overtreding_types', 'overtredingen.overtreding_type_id', '=', 'overtreding_types.id')
            ->when($startDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '<=', $endDate))
            ->select('overtreding_types.code', 'overtreding_types.omschrijving as description', DB::raw('count(*) as count'))
            ->groupBy('overtreding_types.code', 'overtreding_types.omschrijving')
            ->orderByDesc('count')
            ->limit(10)->get();

        $roundsPerUser = ControleRonde::query()
            ->when($startDate, fn($q) => $q->whereDate('start_tijd', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('start_tijd', '<=', $endDate))
            ->select('user_id', DB::raw('count(*) as total'))->groupBy('user_id')->pluck('total', 'user_id');

        $violationsPerUser = Overtreding::query()
            ->join('controle_rondes', 'overtredingen.controle_ronde_id', '=', 'controle_rondes.id')
            ->when($startDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '<=', $endDate))
            ->select('controle_rondes.user_id', DB::raw('count(*) as total'))->groupBy('controle_rondes.user_id')->pluck('total', 'user_id');

        $users = User::whereIn('role', ['Controleur', 'Beheerder', 'Coördinator'])->orderBy('name')->get();
        $byController = $users->map(function ($user) use ($roundsPerUser, $violationsPerUser) {
            return ['name' => $user->name, 'rounds' => $roundsPerUser[$user->id] ?? 0, 'violations' => $violationsPerUser[$user->id] ?? 0];
        })->sortByDesc('rounds')->values();

        $recidivism = Overtreding::query()
            ->join('controle_rondes', 'overtredingen.controle_ronde_id', '=', 'controle_rondes.id')
            ->whereNotNull('vispasnummer')->where('vispasnummer', '!=', '')
            ->when($startDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('controle_rondes.start_tijd', '<=', $endDate))
            ->select('vispasnummer', DB::raw('count(*) as count'), DB::raw('MAX(overtredingen.created_at) as last_violation_date'))
            ->groupBy('vispasnummer')->having('count', '>', 1)->orderByDesc('count')->limit(10)->get()
            ->map(fn($item) => ['vispasnummer' => $item->vispasnummer, 'count' => $item->count, 'last_violation_date' => \Carbon\Carbon::parse($item->last_violation_date)->format('d-m-Y H:i')]);

        // 3. PDF Genereren
        $pdf = Pdf::loadView('pdf.report', ['totals' => ['rounds' => $totalRounds, 'violations' => $totalViolations, 'active_controllers' => $activeControllers], 'byWater' => $byWater, 'byType' => $byType, 'byController' => $byController, 'recidivism' => $recidivism, 'filters' => ['start_date' => $startDate, 'end_date' => $endDate, 'user_name' => 'Automatische Rapportage'], 'generated_at' => now()->format('d-m-Y H:i')]);

        // 4. Email Versturen
        Mail::send([], [], function ($message) use ($email, $pdf, $period, $startDate, $endDate) {
            $message
                ->to($email)
                ->subject("Automatische rapportage HVLD – " . ucfirst($period))
                ->html(
                    "Beste bestuur,<br><br>
                    Hierbij ontvangt u de automatische rapportage vanuit het
                    <strong>HVLD Registratiesysteem voor Controleurs Sportvisserij</strong>
                    over de periode <strong>{$startDate}</strong> t/m <strong>{$endDate}</strong>.<br><br>
                    Deze rapportage is periodiek gegenereerd en toegevoegd als bijlage bij deze e-mail.<br><br>
                    Mocht u vragen hebben naar aanleiding van deze rapportage, dan kunt u contact opnemen via de bekende kanalen.<br><br>
                    Met vriendelijke groet,<br><br>
                    HVLD Registratiesysteem voor Controleurs Sportvisserij"
                )
                ->attachData(
                    $pdf->output(),
                    "Rapportage_{$period}.pdf",
                    ['mime' => 'application/pdf']
                );
        });

        $this->info("Rapport succesvol verzonden naar $email");
        return 0;
    }
}