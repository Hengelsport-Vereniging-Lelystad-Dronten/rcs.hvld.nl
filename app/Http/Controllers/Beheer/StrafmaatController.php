<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Models\Strafmaat; // Importeer het Strafmaat Model
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB; // NIEUW: Importeer de DB Facade voor transacties
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller voor het beheer van Strafmaten.
 * Deze controller verzorgt alle CRUD-operaties (Create, Read, Update, Delete)
 * voor de 'strafmaten' database tabel en de bijbehorende Vue/Inertia pagina's
 * in de 'Beheer/Strafmaten' map.
 */
class StrafmaatController extends Controller
{
    /**
     * Toon het overzicht van alle gedefinieerde strafmaten.
     * Standaard sorteren we nu op de 'order_id' voor een vaste, handmatige volgorde (drag-and-drop).
     * @param Request $request Het inkomende HTTP-verzoek (om sorteerparameters op te halen).
     * @return Response Inertia Response die de 'Beheer/Strafmaten/Index' component rendert.
     */
    public function index(Request $request): Response
    {
        // 1. Haal sorteerparameters op uit de request, met veilige defaults.
        // STANDAARD SORTERING OP ORDER_ID: We sorteren nu standaard op order_id, essentieel voor drag-and-drop.
        $sortBy = $request->query('sort_by', 'order_id');
        $sortDirection = $request->query('sort_direction', 'asc');

        // 2. Valideer de sorteerrichting.
        $sortDirection = strtolower($sortDirection);
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // 3. Valideer de sorteerkolom. Alleen toegestane kolommen mogen worden gebruikt.
        $allowedColumns = ['id', 'code', 'omschrijving', 'order_id', 'created_at']; 
        if (!in_array($sortBy, $allowedColumns)) {
            $sortBy = 'order_id'; // Val terug op order_id voor de vaste volgorde
        }

        // 4. Haal alle strafmaten op uit de database met de dynamische sortering.
        $strafmaten = Strafmaat::orderBy($sortBy, $sortDirection)->get();

        return Inertia::render('Beheer/Strafmaten/Index', [
            // Geef de lijst met gesorteerde strafmaten mee.
            'strafmaten' => $strafmaten,
            
            // Geef de huidige sorteerparameters mee aan de Vue-component (voor de header indicators).
            'currentSortBy' => $sortBy,
            'currentSortDirection' => $sortDirection,
        ]);
    }

    /**
     * Toon het formulier om een nieuwe strafmaat aan te maken.
     * @return Response Inertia Response die de 'Beheer/Strafmaten/CreateEdit' component rendert.
     */
    public function create(): Response
    {
        // Er wordt geen 'strafmaat' prop doorgegeven, dus de component weet dat het om 'create' gaat.
        return Inertia::render('Beheer/Strafmaten/CreateEdit');
    }

    /**
     * Sla een nieuw aangemaakte strafmaat op in de database na validatie.
     * @param Request $request Het inkomende HTTP-verzoek met de formuliergegevens.
     * @return RedirectResponse Redirect naar de indexpagina met een succesbericht.
     */
    public function store(Request $request): RedirectResponse
    {
        // Valideer de inkomende gegevens, inclusief unieke en verplichte order_id.
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', Rule::unique('strafmaten', 'code')],
            'omschrijving' => ['required', 'string', 'max:500'],
            'order_id' => ['required', 'integer', 'min:1', Rule::unique('strafmaten', 'order_id')], 
        ]);

        Strafmaat::create($validated);

        return Redirect::route('beheer.strafmaten.index')
            ->with('success', 'Strafmaat "' . ($validated['code'] ?? $validated['omschrijving']) . '" succesvol aangemaakt.');
    }

    /**
     * Toon het formulier om een bestaande strafmaat te bewerken.
     * De Route Model Binding variabele is hernoemd naar $strafmaten, zoals gevraagd.
     * @param Strafmaat $strafmaten Het model van de te bewerken strafmaat (Route Model Binding).
     * @return Response Inertia Response die de 'Beheer/Strafmaten/CreateEdit' component rendert.
     */
    public function edit(Strafmaat $strafmaten): Response
    {
        // We geven de modeldata als array door aan de Vue-component.
        return Inertia::render('Beheer/Strafmaten/CreateEdit', [
            // De prop die naar de Vue-component gaat, blijft 'strafmaat' om de bestaande Vue code niet te breken.
            'strafmaat' => $strafmaten->toArray(),
        ]);
    }

    /**
     * Werk een bestaande strafmaat bij in de database na validatie.
     * De Route Model Binding variabele is hernoemd naar $strafmaten, zoals gevraagd.
     * @param Request $request Het inkomende HTTP-verzoek met de gewijzigde formuliergegevens.
     * @param Strafmaat $strafmaten Het model van de bij te werken strafmaat.
     * @return RedirectResponse Redirect naar de indexpagina met een succesbericht.
     */
    public function update(Request $request, Strafmaat $strafmaten): RedirectResponse
    {
        // Valideer de inkomende gegevens. order_id en code moeten uniek zijn, behalve voor het huidige record.
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', Rule::unique('strafmaten', 'code')->ignore($strafmaten->id)],
            'omschrijving' => ['required', 'string', 'max:500'],
            'order_id' => ['required', 'integer', 'min:1', Rule::unique('strafmaten', 'order_id')->ignore($strafmaten->id)],
        ]);

        $strafmaten->update($validated);

        return Redirect::route('beheer.strafmaten.index')
            ->with('success', 'Strafmaat "' . ($strafmaten->code ?? $strafmaten->omschrijving) . '" succesvol bijgewerkt.');
    }

    /**
     * Verwijder een strafmaat uit de database.
     * De Route Model Binding variabele is hernoemd naar $strafmaten, zoals gevraagd.
     * @param Strafmaat $strafmaten Het model van de te verwijderen strafmaat.
     * @return RedirectResponse Redirect naar de indexpagina met een succesbericht.
     */
    public function destroy(Strafmaat $strafmaten): RedirectResponse
    {
        $strafmaatCode = $strafmaten->code ?? $strafmaten->omschrijving;
        $strafmaten->delete();

        return Redirect::route('beheer.strafmaten.index')
            ->with('success', 'Strafmaat "' . $strafmaatCode . '" succesvol verwijderd.');
    }

    /**
     * Werkt de sorteervolgorde (order_id) van meerdere strafmaten bij op basis van een drag-and-drop actie in de UI.
     * * Dit endpoint verwacht een array van strafmaat ID's in de nieuwe, gewenste volgorde.
     * De controller itereert over deze array en wijst een nieuwe, sequentiële 'order_id' toe aan elke strafmaat.
     * * @param Request $request Verwacht een veld genaamd 'ids' dat een geordende array van ID's bevat.
     * @return \Illuminate\Http\JsonResponse JSON response met succes of foutmelding.
     */
    public function updateOrder(Request $request)
    {
        // 1. Valideer de inkomende request. 'ids' moet een array zijn en alle elementen moeten integers zijn.
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            // Zorgt ervoor dat elke ID een integer is en daadwerkelijk bestaat in de 'strafmaten' tabel.
            'ids.*' => ['integer', 'exists:strafmaten,id'], 
        ]);

        $ids = $validated['ids'];

        // 2. Transactie om de database consistentie te garanderen tijdens het updaten van meerdere records.
        DB::transaction(function () use ($ids) {
            foreach ($ids as $index => $id) {
                // De nieuwe order_id is de index + 1 (om te beginnen bij 1 i.p.v. 0).
                $newOrderId = $index + 1;

                // Werk de order_id bij. Gebruik where('id', $id) in plaats van find($id) gevolgd door update()
                // Dit is efficiënter voor een bulk update in een lus.
                Strafmaat::where('id', $id)->update(['order_id' => $newOrderId]);
            }
        });

        // 3. Stuur een succesvolle JSON-respons terug.
        return response()->json(['message' => 'Volgorde succesvol bijgewerkt.'], 200);
    }
}