<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOvertredingRequest;
use App\Models\ControleRonde;
use App\Models\Overtreding;
use Illuminate\Support\Facades\Mail;
use App\Mail\VispasIngenomenMail;
use App\Models\OvertredingType;
use Illuminate\Http\RedirectResponse;

/**
 * Controller: OvertredingController
 *
 * Behandelt het aanmaken van overtredingen gekoppeld aan een controle-ronde.
 * Voor deze applicatie wordt alleen de `store`-actie gebruikt — overtredingen
 * worden genest onder een ControleRonde en hoeven niet los te worden beheerd.
 */
class OvertredingController extends Controller
{
    /**
     * Store a newly created overtreding in storage, gekoppeld aan een actieve ronde.
     *
     * Valideert de input, controleert of de ronde actief is, bepaalt de juiste maatregel
     * (standaard of recidive) en maakt de overtreding aan. Stuurt eventueel een notificatie
     * wanneer een vispas is ingenomen.
     *
     * @param  \App\Http\Requests\StoreOvertredingRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreOvertredingRequest $request)
    {
        // 1. Validatie is al afgehandeld door StoreOvertredingRequest
        $validated = $request->validated();

        // 2. Controleer of de ronde actief is
        $ronde = ControleRonde::findOrFail($validated['controle_ronde_id']);
        
        if ($ronde->status !== 'Actief') {
            return redirect()->back()
                ->with('error', 'Overtredingen kunnen alleen worden toegevoegd aan een actieve ronde.');
        }

        // 3. Bepaal de te nemen maatregel op basis van recidive
        $genomenMaatregel = $this->determineMaatregel(
            (int) $validated['overtreding_type_id'],
            $validated['vispasnummer'] ?? null
        );

        // 4. Overtreding aanmaken met de vastgestelde maatregel
        $overtredingData = $validated;
        $overtredingData['genomen_maatregel'] = $genomenMaatregel;
        $overtredingData['status'] = Overtreding::STATUS_ACTIEF;

        $overtreding = Overtreding::create($overtredingData);

        if ($overtreding->vispas_ingenomen) {
            $recipient = config('mail.vispas_ingenomen_recipient');
            if ($recipient) {
                $overtreding->load('controleRonde.user', 'overtredingType');
                Mail::to($recipient)->send(new VispasIngenomenMail($overtreding));
            }
        }

        // 5. Terugsturen naar de ronde-pagina met een succesbericht
        return redirect()->route('controles.show', $ronde->id)
            ->with('message', 'Overtreding succesvol geregistreerd.');
    }

    public function update(Request $request, Overtreding $overtreding): RedirectResponse
    {
        $this->assertMutatieToegestaan($overtreding);

        if ($overtreding->status !== Overtreding::STATUS_ACTIEF) {
            return back()->with('error', 'Alleen actieve overtredingen kunnen worden gewijzigd.');
        }

        $validated = $request->validate([
            'overtreding_type_id' => 'required|exists:overtreding_types,id',
            'vispasnummer' => 'nullable|string|max:50',
            'details' => 'nullable|string',
            'vispas_ingenomen' => 'nullable|boolean',
        ]);

        $validated['genomen_maatregel'] = $this->determineMaatregel(
            (int) $validated['overtreding_type_id'],
            $validated['vispasnummer'] ?? null,
            $overtreding->id
        );

        $oldData = $overtreding->only([
            'overtreding_type_id',
            'vispasnummer',
            'details',
            'vispas_ingenomen',
            'genomen_maatregel',
            'status',
        ]);

        $overtreding->update($validated);

        activity()
            ->performedOn($overtreding)
            ->withProperties(['old' => $oldData, 'new' => $validated])
            ->log('Overtreding gewijzigd');

        return redirect()
            ->route('controles.show', $overtreding->controle_ronde_id)
            ->with('message', 'Overtreding succesvol bijgewerkt.');
    }

    public function annuleer(Request $request, Overtreding $overtreding): RedirectResponse
    {
        $this->assertMutatieToegestaan($overtreding);

        if ($overtreding->status === Overtreding::STATUS_GEANNULEERD) {
            return back()->with('error', 'Deze overtreding is al geannuleerd.');
        }

        $validated = $request->validate([
            'annulatie_reden' => 'required|string|min:5|max:1000',
        ]);

        $oldData = $overtreding->only(['status', 'annulatie_reden', 'geannuleerd_door', 'geannuleerd_op']);

        $overtreding->update([
            'status' => Overtreding::STATUS_GEANNULEERD,
            'annulatie_reden' => $validated['annulatie_reden'],
            'geannuleerd_door' => auth()->id(),
            'geannuleerd_op' => now(),
        ]);

        activity()
            ->performedOn($overtreding)
            ->withProperties([
                'old' => $oldData,
                'new' => $overtreding->only(['status', 'annulatie_reden', 'geannuleerd_door', 'geannuleerd_op']),
            ])
            ->log('Overtreding geannuleerd');

        return redirect()
            ->route('controles.show', $overtreding->controle_ronde_id)
            ->with('message', 'Overtreding is geannuleerd en uitgesloten van recidive/rapportage.');
    }

    private function assertMutatieToegestaan(Overtreding $overtreding): void
    {
        $user = auth()->user();
        $isOwner = (int) $overtreding->controleRonde->user_id === (int) $user->id;
        $isBeheerder = method_exists($user, 'isBeheerder') && $user->isBeheerder();

        abort_unless($isOwner || $isBeheerder, 403);
    }

    private function determineMaatregel(int $overtredingTypeId, ?string $vispasnummer, ?int $excludeOvertredingId = null): string
    {
        $offenseCount = 0;

        if ($vispasnummer) {
            $query = Overtreding::actief()
                ->where('vispasnummer', $vispasnummer)
                ->where('overtreding_type_id', $overtredingTypeId);

            if ($excludeOvertredingId) {
                $query->where('id', '!=', $excludeOvertredingId);
            }

            $offenseCount = $query->count();
        }

        $overtredingType = OvertredingType::with('defaultStrafmaat', 'recidiveStrafmaat')->findOrFail($overtredingTypeId);

        if ($offenseCount === 0) {
            return $overtredingType->defaultStrafmaat->omschrijving ?? 'Standaard maatregel niet gevonden';
        }

        if ($offenseCount === 1) {
            return $overtredingType->recidiveStrafmaat->omschrijving ?? 'Recidive maatregel niet gevonden';
        }

        return 'justitie';
    }

    // Voor deze app hebben we geen index, show, edit, update of destroy nodig, 
    // aangezien overtredingen genest zijn binnen ControleRonde.
}
