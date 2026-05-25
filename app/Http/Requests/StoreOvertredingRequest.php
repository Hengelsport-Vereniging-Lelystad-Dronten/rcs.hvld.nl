<?php

namespace App\Http\Requests;

use App\Enums\ConstateringWijze; // Zie suggestie hieronder
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOvertredingRequest extends FormRequest
{
    /**
     * Bepaalt of de gebruiker geautoriseerd is om deze request te maken.
     */
    public function authorize(): bool
    {
        // Hier kan autorisatielogica worden toegevoegd, bijv. op basis van de rol van de gebruiker.
        // Voor nu gaan we ervan uit dat een ingelogde gebruiker dit mag.
        return true;
    }

    /**
     * Haalt de validatieregels op die van toepassing zijn op de request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            // Relatie naar de controleronde
            'controle_ronde_id' => ['required', 'exists:controle_rondes,id'],

            // WAT: Verplicht en moet een bestaand type zijn.
            'overtreding_type_id' => ['required', 'exists:overtreding_types,id'],

            // WANNEER: Verplicht en een geldige datum in het verleden of heden.
            'geconstateerd_op' => ['required', 'date', 'before_or_equal:now'],

            // WAAR: Verplicht en moet een valide JSON-string zijn.
            'locatie_details' => ['required', 'json'],

            // HOE: Verplicht en moet een van de vooraf gedefinieerde waardes zijn.
            'constatering_wijze' => ['required', Rule::in(ConstateringWijze::values())],

            // WAAROM: Optioneel.
            'aanleiding' => ['nullable', 'string', 'max:255'],

            // WAARMEE: Standaard optioneel, maar kan verplicht worden.
            'middel' => ['nullable', 'string', 'max:255'],

            // Overige velden
            'vispasnummer' => ['nullable', 'string', 'max:50'],
            'vispas_foto_path' => ['nullable', 'string', 'max:255', 'starts_with:vispassen/'],
            'vispas_scan_confidence' => ['nullable', 'integer', 'min:0', 'max:100'],
            'details' => ['nullable', 'string'],
            'vispas_ingenomen' => ['required', 'boolean'],
        ];

        // --- DYNAMISCHE VALIDATIE VOORBEELD ---
        // Als het overtredingstype 'Varen met te hoge snelheid' is (bv. ID 5),
        // dan wordt het veld 'middel' (de boot) verplicht.
        // Dit is een krachtige manier om de UI en datakwaliteit te sturen.
        $rules['middel'] = [
            'required_if:overtreding_type_id,5',
            'nullable', // Belangrijk om 'nullable' te houden voor andere gevallen
            'string',
            'max:255'
        ];

        return $rules;
    }

    /**
     * Optionele methode om de validatieberichten aan te passen.
     */
    public function messages(): array
    {
        return [
            'geconstateerd_op.before_or_equal' => 'De datum van constatering kan niet in de toekomst liggen.',
            'middel.required_if' => 'Het veld \'Waarmee\' is verplicht voor dit type overtreding.',
        ];
    }
}
