<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Models\OvertredingType;
use App\Models\Strafmaat; 
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB; // Nodig voor de updateOrder transactie

/**
 * Controller: OvertredingTypeController
 *
 * Beheert overtredingstypes in het beheerdersgedeelte. Deze controller biedt
 * functionaliteit voor het aanmaken, bewerken, verwijderen en herschikken
 * van overtredingstypen en exposeert deze aan de Inertia/Vue frontend.
 */
class OvertredingTypeController extends Controller
{
    /**
     * Toont de lijst van overtredingstypen, gesorteerd op sort_order.
     */
    public function index()
    {
        // Haal types op, inclusief de gerelateerde strafmaten om N+1 queries te voorkomen.
        // Sorteer op de 'sort_order' kolom, en dan op 'code' als fallback.
        $types = OvertredingType::with(['defaultStrafmaat', 'recidiveStrafmaat'])
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return Inertia::render('Beheer/OvertredingTypes/Index', [
            'types' => $types
        ]);
    }

    /**
     * Verwerkt de update van de sorteervolgorde van de overtredingstypes.
     * * @param \Illuminate\Http\Request $request
     */
    public function updateOrder(Request $request)
    {
        // Valideer de array van items die de ID en de nieuwe sorteervolgorde bevatten.
        $request->validate([
            'types' => 'required|array',
            'types.*.id' => 'required|exists:overtreding_types,id',
            'types.*.sort_order' => 'required|integer',
        ]);

        // Gebruik een database transactie om er zeker van te zijn dat alle updates slagen.
        DB::transaction(function () use ($request) {
            foreach ($request->input('types') as $typeData) {
                OvertredingType::where('id', $typeData['id'])
                    ->update(['sort_order' => $typeData['sort_order']]);
            }
        });

        // Stuur een lege 200 respons terug, Inertia hoeft de pagina niet volledig opnieuw te laden.
        return response()->json(['message' => 'Sorteervolgorde succesvol bijgewerkt.'], 200);
    }
    
    /**
     * Toont het formulier voor het aanmaken van een nieuw overtredingstype.
     */
    public function create()
    {
        // Haal alle beschikbare strafmaten op
        $strafmaten = Strafmaat::select('id', 'omschrijving')->get();
        
        return Inertia::render('Beheer/OvertredingTypes/CreateEdit', [
            'strafmaten' => $strafmaten,
        ]);
    }

    /**
     * Slaat een nieuw aangemaakt overtredingstype op.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:overtreding_types,code', 
            'omschrijving' => 'required|string|max:500',
            'default_strafmaat_id' => 'required|exists:strafmaten,id',
            'recidive_strafmaat_id' => 'nullable|exists:strafmaten,id',
        ]);
        
        // Bepaal de volgende sort_order om het nieuwe item onderaan toe te voegen.
        $nextSortOrder = OvertredingType::max('sort_order') + 1;
        
        OvertredingType::create(array_merge($request->all(), ['sort_order' => $nextSortOrder]));

        return redirect()->route('beheer.overtreding_types.index')
            ->with('message', 'Overtredingstype ' . $request->code . ' succesvol toegevoegd.');
    }

    /**
     * Toont het formulier voor het bewerken van een bestaand overtredingstype.
     */
    public function edit(string $id)
    {
        $overtredingType = OvertredingType::findOrFail($id);
        $strafmaten = Strafmaat::select('id', 'omschrijving')->get();

        return Inertia::render('Beheer/OvertredingTypes/CreateEdit', [
            'type' => $overtredingType,
            'strafmaten' => $strafmaten,
        ]);
    }

    /**
     * Werkt het opgegeven overtredingstype bij in de database.
     */
    public function update(Request $request, string $id)
    {
        $overtredingType = OvertredingType::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:10|unique:overtreding_types,code,' . $overtredingType->id, 
            'omschrijving' => 'required|string|max:500',
            'default_strafmaat_id' => 'required|exists:strafmaten,id',
            'recidive_strafmaat_id' => 'nullable|exists:strafmaten,id',
        ]);

        $overtredingType->update($request->all());

        return redirect()->route('beheer.overtreding_types.index')
            ->with('message', 'Overtredingstype ' . $overtredingType->code . ' succesvol bijgewerkt.');
    }

    /**
     * Verwijdert het opgegeven overtredingstype.
     */
    public function destroy(string $id)
    {
        $overtredingType = OvertredingType::findOrFail($id);
        $overtredingType->delete();

        return redirect()->route('beheer.overtreding_types.index')
            ->with('message', 'Overtredingstype ' . $overtredingType->code . ' succesvol verwijderd.');
    }
}