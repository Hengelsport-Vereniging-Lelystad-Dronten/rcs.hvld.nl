<?php

namespace App\Http\Controllers;

use App\Enums\ConstateringWijze;
use App\Models\ControleRonde;
use App\Models\Overtreding;
use App\Models\OvertredingType;
use App\Models\Strafmaat;
use App\Models\Water;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ControleRondeController extends Controller
{
    public function index()
    {
        $rondes = ControleRonde::with(['user', 'water'])
            ->withCount([
                'overtredingen as overtredingen_count' => fn ($q) => $q->where('status', Overtreding::STATUS_ACTIEF),
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
        $request->validate([
            'water_id' => 'required|exists:waters,id',
            'start_tijd' => 'nullable|date',
        ]);

        $ronde = ControleRonde::create([
            'user_id' => auth()->id(),
            'water_id' => $request->water_id,
            'start_tijd' => $request->start_tijd ? $request->start_tijd : now(),
            'status' => 'Actief',
        ]);

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

        return Inertia::render('ControleRondes/Show', [
            'ronde' => $controle,
            'overtredingTypes' => $overtredingTypes,
            'strafmaten' => $strafmaten,
            'constateringWijzes' => ConstateringWijze::values(),
        ]);
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(ControleRonde $controle)
    {
        if ($controle->overtredingen()->exists()) {
            return redirect()->route('controles.show', $controle->id)
                ->with('error', 'Deze ronde kan niet worden verwijderd omdat er overtredingen aan gekoppeld zijn.');
        }

        $controle->delete();

        return redirect()->route('controles.index')
            ->with('success', 'Controle ronde succesvol geannuleerd.');
    }

    public function afronden(Request $request, ControleRonde $controleRonde)
    {
        $request->validate([
            'opmerkingen' => 'nullable|string',
            'eind_tijd' => 'nullable|date',
        ]);

        if ($controleRonde->status !== 'Actief') {
            return redirect()->back()
                ->with('error', 'Deze ronde is al afgerond.');
        }

        $controleRonde->update([
            'eind_tijd' => $request->eind_tijd ? $request->eind_tijd : now(),
            'opmerkingen' => $request->opmerkingen,
            'status' => 'Afgerond',
        ]);

        return redirect()->route('controles.index')
            ->with('message', 'Controle ronde op ' . $controleRonde->water->naam . ' succesvol afgerond.');
    }

    public function overtredingen()
    {
        //
    }
}
