<?php

namespace App\Http\Controllers;

use App\Enums\ConstateringWijze;
use App\Models\ControleRonde;
use App\Models\Overtreding;
use App\Models\OvertredingType;
use App\Models\Strafmaat;
use App\Models\Water;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ControleRondeController extends Controller
{
    public function index()
    {
        $rondes = ControleRonde::with(['user', 'water'])
            ->where('status', ControleRonde::STATUS_AFGEROND)
            ->withCount([
                'overtredingen as overtredingen_count' => fn ($q) => $q->where('status', Overtreding::STATUS_ACTIEF),
                'overtredingen as overtredingen_zonder_type_00_count' => fn ($q) => $q->where('status', Overtreding::STATUS_ACTIEF)
                    ->whereHas('overtredingType', fn ($q) => $q->where('code', '!=', '00')),
            ])
            ->latest()
            ->get();

        return Inertia::render('ControleRondes/Index', [
            'rondes' => $rondes,
        ]);
    }

    public function create()
    {
        $waters = Water::orderBy('beheersgebied')->orderBy('naam')->get();

        return Inertia::render('ControleRondes/Start', [
            'waters' => $waters,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'water_id' => 'required|exists:waters,id',
            'start_tijd' => 'nullable|date',
        ]);

        $ronde = ControleRonde::create([
            'user_id' => auth()->id(),
            'water_id' => $validated['water_id'],
            'start_tijd' => ($validated['start_tijd'] ?? null) ? $validated['start_tijd'] : now(),
            'status' => 'Actief',
        ]);

        activity()
            ->performedOn($ronde)
            ->withProperties([
                'new' => $ronde->only(['user_id', 'water_id', 'start_tijd', 'status']),
            ])
            ->log('Controle ronde gestart');

        return redirect()->route('controles.show', $ronde->id)
            ->with('message', 'Controle ronde op ' . $ronde->water->naam . ' is gestart.');
    }

    public function show(ControleRonde $controle)
    {
        $controle->load([
            'user',
            'water',
            'overtredingen' => fn ($q) => $q->with(['overtredingType', 'geannuleerdDoor'])->latest(),
        ]);

        $overtredingTypes = OvertredingType::with('defaultStrafmaat', 'recidiveStrafmaat')
            ->orderBy('code')
            ->get();

        $strafmaten = Strafmaat::select('id', 'omschrijving')
            ->orderBy('omschrijving')
            ->get();

        $waters = Water::orderBy('beheersgebied')->orderBy('naam')->get();

        return Inertia::render('ControleRondes/Show', [
            'ronde' => $controle,
            'overtredingTypes' => $overtredingTypes,
            'strafmaten' => $strafmaten,
            'constateringWijzes' => ConstateringWijze::values(),
            'waters' => $waters,
            'statusOptions' => ControleRonde::statuses(),
        ]);
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, ControleRonde $controle)
    {
        $user = auth()->user();
        $isOwner = (int) $controle->user_id === (int) $user->id;
        $isBeheerder = method_exists($user, 'isBeheerder') && $user->isBeheerder();
        if (!$isOwner && !$isBeheerder) {
            activity()
                ->performedOn($controle)
                ->withProperties(['attempted_by' => $user->id])
                ->log('Controle ronde update geweigerd (geen rechten)');
            abort(403);
        }

        $validated = $request->validate([
            'water_id' => 'required|exists:waters,id',
            'start_tijd' => 'required|date',
            'opmerkingen' => 'nullable|string',
            'status' => ['required', Rule::in(ControleRonde::statuses())],
        ]);

        if (($validated['status'] ?? null) === ControleRonde::STATUS_AFGEROND && !$controle->eind_tijd) {
            $validated['eind_tijd'] = now();
        }

        $oldData = $controle->only(['water_id', 'start_tijd', 'opmerkingen', 'status', 'eind_tijd']);

        $controle->update($validated);

        activity()
            ->performedOn($controle)
            ->withProperties(['old' => $oldData, 'new' => $validated])
            ->log('Controle ronde bijgewerkt');

        return redirect()->route('controles.show', $controle->id)
            ->with('message', 'Controle ronde succesvol bijgewerkt.');
    }

    public function destroy(ControleRonde $controle)
    {
        if ($controle->overtredingen()->exists()) {
            activity()
                ->performedOn($controle)
                ->withProperties([
                    'overtredingen_count' => $controle->overtredingen()->count(),
                ])
                ->log('Controle ronde verwijderen geweigerd (heeft overtredingen)');

            return redirect()->route('controles.show', $controle->id)
                ->with('error', 'Deze ronde kan niet worden verwijderd omdat er overtredingen aan gekoppeld zijn.');
        }

        $oldData = $controle->only(['id', 'user_id', 'water_id', 'start_tijd', 'eind_tijd', 'opmerkingen', 'status']);

        activity()
            ->performedOn($controle)
            ->withProperties(['old' => $oldData])
            ->log('Controle ronde verwijderd');

        $controle->delete();

        return redirect()->route('controles.index')
            ->with('success', 'Controle ronde succesvol geannuleerd.');
    }

    public function afronden(Request $request, ControleRonde $controleRonde)
    {
        $validated = $request->validate([
            'opmerkingen' => 'nullable|string',
            'eind_tijd' => 'nullable|date',
        ]);

        if ($controleRonde->status !== 'Actief') {
            activity()
                ->performedOn($controleRonde)
                ->withProperties(['status' => $controleRonde->status])
                ->log('Controle ronde afronden geweigerd (niet actief)');

            return redirect()->back()
                ->with('error', 'Deze ronde is al afgerond.');
        }

        $oldData = $controleRonde->only(['eind_tijd', 'opmerkingen', 'status']);

        $controleRonde->update([
            'eind_tijd' => ($validated['eind_tijd'] ?? null) ? $validated['eind_tijd'] : now(),
            'opmerkingen' => $validated['opmerkingen'] ?? null,
            'status' => 'Afgerond',
        ]);

        activity()
            ->performedOn($controleRonde)
            ->withProperties([
                'old' => $oldData,
                'new' => $controleRonde->only(['eind_tijd', 'opmerkingen', 'status']),
            ])
            ->log('Controle ronde afgerond');

        return redirect()->route('controles.index')
            ->with('message', 'Controle ronde op ' . $controleRonde->water->naam . ' succesvol afgerond.');
    }

    public function overtredingen()
    {
        //
    }
}
