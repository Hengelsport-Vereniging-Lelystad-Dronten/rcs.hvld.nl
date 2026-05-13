<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Models\OverlastMelding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OverlastMeldingController extends Controller
{
    /**
     * Toon het beheer-overzicht van overlastmeldingen.
     */
    public function index(Request $request): Response
    {
        $query = OverlastMelding::with('verwerktDoor')->latest();

        if ($request->has('status')) {
            if ($request->get('status') === 'all') {
                // Toon expliciet alle statussen.
            } elseif (in_array($request->get('status'), OverlastMelding::statuses(), true)) {
                $query->where('status', $request->get('status'));
            } else {
                $query->whereIn('status', [
                    OverlastMelding::STATUS_NIEUW,
                    OverlastMelding::STATUS_IN_BEHANDELING,
                ]);
            }
        } else {
            $query->whereIn('status', [
                OverlastMelding::STATUS_NIEUW,
                OverlastMelding::STATUS_IN_BEHANDELING,
            ]);
        }

        if (
            $request->has('categorie')
            && in_array($request->get('categorie'), OverlastMelding::categories(), true)
        ) {
            $query->where('categorie', $request->get('categorie'));
        }

        $perPage = min(max((int) $request->get('per_page', 15), 5), 100);
        $meldingen = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Beheer/OverlastMeldingen/Index', [
            'meldingen' => $meldingen,
            'filters' => [
                'status' => $request->get('status', 'all'),
                'categorie' => $request->get('categorie', 'all'),
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Toon de detailpagina van een melding.
     */
    public function show(OverlastMelding $melding): Response
    {
        return Inertia::render('Beheer/OverlastMeldingen/Show', [
            'melding' => $melding->load('verwerktDoor'),
        ]);
    }

    /**
     * Werk de status van een melding bij vanuit de beheerinterface.
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
                $oldStatus = $melding->status;

                $melding->status = $validated['status'];
                $melding->verwerkt_door = $request->user()->id;
                $melding->verwerkt_op = now();

                if (!empty($validated['interne_notities'])) {
                    $melding->interne_notities = ($melding->interne_notities ?? '')
                        . "\n\n[" . now()->format('Y-m-d H:i') . "] "
                        . $validated['interne_notities'];
                }

                if ($validated['status'] === OverlastMelding::STATUS_AFGEWEZEN) {
                    $melding->afgewezen_reden = $validated['afgewezen_reden'];
                }

                $melding->save();

                activity()
                    ->on($melding)
                    ->withProperties([
                        'old_status' => $oldStatus,
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
}
