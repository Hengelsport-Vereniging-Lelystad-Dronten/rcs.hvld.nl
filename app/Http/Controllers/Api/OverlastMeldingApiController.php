<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOverlastMeldingRequest;
use App\Mail\OverlastMeldingReceivedMail;
use App\Models\OverlastMelding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * API Controller: OverlastMeldingApiController
 *
 * Biedt API endpoints voor het publiek meldformulier voor sportvisserij en dierenwelzijn.
 *
 * Endpoints:
 * - POST /api/overlast-meldingen - Opslaan van een nieuwe melding (publiek, geen auth)
 * - GET /api/overlast-meldingen/{id} - Ophalen melding-details (voor beheerders)
 * - GET /api/overlast-meldingen - Overzicht (alleen beheerders)
 * - PATCH /api/overlast-meldingen/{id}/status - Status wijzigen (alleen beheerders)
 */
class OverlastMeldingApiController extends Controller
{
    /**
     * Store a newly created overlast melding.
     *
     * Dit is een PUBLIEKE endpoint - geen authenticatie vereist.
     * Valideert de input en slaat de melding op in de database.
     *
     * @param StoreOverlastMeldingRequest $request
     * @return JsonResponse
     */
    public function store(StoreOverlastMeldingRequest $request)
    {
        try {
            $validated = $request->validated();

            // Normaliseer bool en overtule anoniem wanneer contactgegevens aanwezig zijn.
            $validated['melder_anoniem'] = filter_var($validated['melder_anoniem'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!empty($validated['melder_naam']) || !empty($validated['melder_email']) || !empty($validated['melder_telefoon'])) {
                $validated['melder_anoniem'] = false;
            }

            // Als anoniem geselecteerd is, wis persoonlijke details altijd uit
            if ($validated['melder_anoniem']) {
                $validated['melder_naam'] = null;
                $validated['melder_email'] = null;
                $validated['melder_telefoon'] = null;
            }

            // Verwerk eventuele geüploade bestanden en sla op in publieke storage
            $geupload = [];
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $bestand) {
                    if ($bestand && $bestand->isValid()) {
                        $pad = $bestand->store('overlast_meldingen', 'public');
                        if ($pad) {
                            $geupload[] = $pad;
                        }
                    }
                }
            }

            if (!empty($geupload)) {
                $validated['fotos'] = $geupload;
            } else {
                $validated['fotos'] = $validated['fotos'] ?? null;
            }

            // Opslaan van de melding
            $melding = OverlastMelding::create($validated);

            // Log de actie voor audit trail (optioneel)
            activity()
                ->on($melding)
                ->log('Overlast melding ingediend via publiek formulier');

            // E-mail notificatie voor beheerders
            $recipientString = config('mail.overlast_melding_recipient');
            if ($recipientString) {
                $recipients = array_filter(array_map('trim', explode(',', $recipientString)));
                if (!empty($recipients)) {
                    try {
                        Mail::to($recipients)->send(new OverlastMeldingReceivedMail($melding, 'admin'));
                    } catch (\Throwable $e) {
                        activity()
                            ->on($melding)
                            ->withProperties(['error' => $e->getMessage()])
                            ->log('Mislukte e-mail notificatie voor overlast melding (admin)');
                    }
                }
            }

            // Bevestigingsmail voor indiener (als er een email is en niet-anoniem)
            if (!empty($melding->melder_email) && !$melding->melder_anoniem) {
                try {
                    Mail::to($melding->melder_email)->send(new OverlastMeldingReceivedMail($melding, 'melder'));
                } catch (\Throwable $e) {
                    activity()
                        ->on($melding)
                        ->withProperties(['error' => $e->getMessage()])
                        ->log('Mislukte e-mail notificatie voor overlast melding (melder)');
                }
            }

            // Terugsturen met succesbericht
            // Check of dit een Inertia request is
            if (request()->header('X-Inertia')) {
                return Inertia::location(route('overlast-meldingen.bedankt'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Uw melding is succesvol ontvangen. Dank u voor uw bijdrage.',
                'melding_id' => $melding->id,
                'status' => $melding->status,
                'created_at' => $melding->created_at->toIso8601String(),
            ], 201);

        } catch (\Exception $e) {
            // Check of dit een Inertia request is
            if (request()->header('X-Inertia')) {
                return back()->withErrors(['general' => 'Er is een fout opgetreden bij het opslaan van uw melding.']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden bij het opslaan van uw melding.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Haal één melding op (voor beheerders).
     *
     * @param OverlastMelding $melding
     * @return JsonResponse
     */
    public function show(OverlastMelding $melding): JsonResponse
    {
        return response()->json($melding->load('verwerktDoor:id,name,email'));
    }

    /**
     * Haal alle meldingen op met filtering (alleen voor beheerders).
     *
     * Query parameters:
     * - status: Filter op status (nieuw, in_behandeling, afgehandeld, afgewezen)
     * - categorie: Filter op categorie van melding
     * - date_from: Meldingen vanaf deze datum
     * - date_to: Meldingen tot deze datum
     * - sort: Sorteer veld (created_at, status, etc.)
     * - order: Sorteervolgorde (asc/desc)
     * - per_page: Paginering (standaard 50)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Basis query
        $query = OverlastMelding::query();

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Categorie filter
        if ($request->has('categorie')) {
            $query->where('categorie', $request->input('categorie'));
        }

        // Datum range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Sortering
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        
        // Whitelist sorteervelden ter voorkoming van SQL injection
        $allowedSorts = ['id', 'status', 'categorie', 'created_at', 'melding_datum_tijd'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, strtolower($order) === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Paginering
        $perPage = min($request->input('per_page', 50), 100);
        $meldingen = $query->with('verwerktDoor:id,name,email')->paginate($perPage);

        return response()->json($meldingen);
    }

    /**
     * Wijzig de status van een melding (alleen beheerders).
     *
     * POST parameters:
     * - status: Nieuwe status (nieuw, in_behandeling, afgehandeld, afgewezen)
     * - interne_notities: Optionele interne notities
     * - afgewezen_reden: Reden van afwijzing (verplicht als status = afgewezen)
     *
     * @param Request $request
     * @param OverlastMelding $melding
     * @return JsonResponse
     */
    public function updateStatus(Request $request, OverlastMelding $melding): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', OverlastMelding::statuses()),
            'interne_notities' => 'nullable|string|max:1000',
            'afgewezen_reden' => 'nullable|required_if:status,afgewezen|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($melding, $validated, $request) {
                // Bijwerken van status
                $melding->status = $validated['status'];
                $melding->verwerkt_door = $request->user()->id;
                $melding->verwerkt_op = now();

                // Interne notities toevoegen (append, niet vervangen)
                if ($validated['interne_notities']) {
                    $melding->interne_notities = ($melding->interne_notities ?? '') 
                        . "\n\n[" . now()->format('Y-m-d H:i') . "] " 
                        . $validated['interne_notities'];
                }

                // Reden van afwijzing (indien van toepassing)
                if ($validated['status'] === OverlastMelding::STATUS_AFGEWEZEN) {
                    $melding->afgewezen_reden = $validated['afgewezen_reden'];
                }

                $melding->save();

                // Log de status wijziging
                activity()
                    ->on($melding)
                    ->withProperties([
                        'old_status' => $melding->getOriginal('status'),
                        'new_status' => $melding->status,
                    ])
                    ->log('Status aangepast naar ' . $melding->getStatusLabel());
            });

            return response()->json([
                'success' => true,
                'message' => 'Status succesvol bijgewerkt.',
                'melding' => $melding->fresh()->load('verwerktDoor:id,name,email'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij het bijwerken van de status.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Verwijder een melding (soft delete).
     *
     * @param OverlastMelding $melding
     * @return JsonResponse
     */
    public function destroy(OverlastMelding $melding): JsonResponse
    {
        try {
            $melding->delete();

            activity()
                ->on($melding)
                ->log('Overlast melding verwijderd');

            return response()->json([
                'success' => true,
                'message' => 'Melding verwijderd.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij het verwijderen van de melding.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Haal beschikbare categorieën op (voor frontend dropdown).
     *
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        $categories = collect(OverlastMelding::categories())
            ->mapWithKeys(function ($category) {
                return [
                    $category => [
                        'value' => $category,
                        'label' => $this->getCategoryLabel($category),
                    ]
                ];
            });

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Haal beschikbare statussen op (voor beheerders).
     *
     * @return JsonResponse
     */
    public function statuses(): JsonResponse
    {
        $statuses = collect(OverlastMelding::statuses())
            ->mapWithKeys(function ($status) {
                return [
                    $status => [
                        'value' => $status,
                        'label' => $this->getStatusLabel($status),
                    ]
                ];
            });

        return response()->json([
            'success' => true,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Helper: Get category label
     */
    private function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'vissterfte' => 'Vissterfte',
            'onjuist_gedrag_vissers' => 'Onjuist gedrag vissers',
            'dierenmishandeling' => 'Dierenmishandeling (vis-gerelateerd)',
            'illegale_visserij' => 'Illegale visserij',
            'vervuiling' => 'Vervuiling met impact op vissen',
            'overig' => 'Overig (binnen scope)',
            default => $category,
        };
    }

    /**
     * Helper: Get status label
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'nieuw' => 'Nieuw',
            'in_behandeling' => 'In behandeling',
            'afgehandeld' => 'Afgehandeld',
            'afgewezen' => 'Afgewezen',
            default => $status,
        };
    }

    /**
     * Haal statistieken op over meldingen (voor beheerders dashboard).
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => OverlastMelding::count(),
            'by_status' => OverlastMelding::select('status')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_categorie' => OverlastMelding::select('categorie')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('categorie')
                ->pluck('count', 'categorie'),
            'unprocessed' => OverlastMelding::where('status', 'nieuw')->count(),
            'rejected' => OverlastMelding::where('status', 'afgewezen')->count(),
            'average_processing_time' => $this->getAverageProcessingTime(),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Helper: Calculate average processing time
     */
    private function getAverageProcessingTime(): ?string
    {
        $avg = OverlastMelding::whereNotNull('verwerkt_op')
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, created_at, verwerkt_op)) as avg_days')
            ->first();

        if ($avg && $avg->avg_days) {
            return round($avg->avg_days, 1) . ' dagen';
        }

        return null;
    }
}
