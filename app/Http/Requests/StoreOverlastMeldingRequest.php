<?php

namespace App\Http\Requests;

use App\Models\OverlastMelding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request: StoreOverlastMeldingRequest
 *
 * Valideert de invoer voor het publieke overlast melding formulier.
 * Dit formulier is specifiek bedoeld voor meldingen over sportvisserij en dierenwelzijn.
 */
class StoreOverlastMeldingRequest extends FormRequest
{
    /**
     * Bepaal of de gebruiker geautoriseerd is om deze request te maken.
     * Dit is een PUBLIEKE endpoint - geen authenticatie vereist.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'melder_anoniem' => filter_var($this->melder_anoniem ?? false, FILTER_VALIDATE_BOOLEAN),
            'categorie_scope_geldig' => filter_var($this->categorie_scope_geldig ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);

        // Als contactgegevens aanwezig zijn, forceer anoniem naar false
        if (!empty($this->melder_naam) || !empty($this->melder_email) || !empty($this->melder_telefoon)) {
            $this->merge(['melder_anoniem' => false]);
        }

        if ($this->melder_anoniem) {
            $this->merge([
                'melder_naam' => null,
                'melder_email' => null,
                'melder_telefoon' => null,
            ]);
        }
    }

    /**
     * Haal de validatieregels op die van toepassing zijn op de request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            /**
             * STAP 1: CATEGORIE
             */
            'categorie' => [
                'required',
                Rule::in(OverlastMelding::categories()),
            ],

            /**
             * STAP 2: MELDING DETAILS
             */
            'beschrijving' => [
                'required',
                'string',
                'min:20',  // Minimaal 20 characters om spam tegen te gaan
                'max:2000',
            ],

            'melding_datum_tijd' => [
                'required',
                'date',
                'before_or_equal:now',  // Mag niet in de toekomst liggen
            ],

            'aantal_vissen' => [
                'nullable',
                'integer',
                'min:1',
                'max:10000',
            ],

            'ernst_situatie' => [
                'nullable',
                Rule::in(['laag', 'midden', 'hoog']),
            ],

            /**
             * STAP 3: LOCATIE
             */
            'locatie_adres' => [
                'nullable',
                'string',
                'max:255',
            ],

            'locatie_details' => [
                'nullable',
                'string',
                // Valideer dat het minimaal latitude/longitude bevat als JSON wordt gegeven
                function ($attribute, $value, $fail) {
                    if ($value) {
                        // Als het een string is, probeer het te decoderen als JSON
                        if (is_string($value)) {
                            $decoded = json_decode($value, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $fail('De locatie details moeten een geldige JSON string zijn.');
                                return;
                            }
                            $value = $decoded;
                        }

                        // Controleer of het een array is met latitude en longitude
                        if (is_array($value) && isset($value['latitude']) && isset($value['longitude'])) {
                            // OK
                        } else {
                            $fail('De locatie details moeten minimaal latitude en longitude bevatten.');
                        }
                    }
                },
            ],

            /**
             * STAP 4: BIJLAGEN
             */
            'fotos' => [
                'nullable',
                'array',
                'max:5',  // Maximaal 5 bestanden
            ],

            'fotos.*' => [
                'file',
                'mimes:jpg,jpeg,png,gif,webp,bmp,heic,pdf,mp4,mov,avi',
                'max:10240', // Max 10 MB per bestand
            ],

            /**
             * STAP 5: MELDER GEGEVENS
             */
            'melder_naam' => [
                'nullable',
                'string',
                'max:100',
            ],

            'melder_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'melder_telefoon' => [
                'nullable',
                'string',
                'max:20',
            ],

            'melder_anoniem' => [
                'required',
                'boolean',
            ],

            /**
             * MISBRUIKPREVENTIE
             */
            'categorie_scope_geldig' => [
                'required',
                'boolean',
                // BELANGRIJK: Deze checkbox MOET aangevinkt zijn
            ],

            'captcha_token' => [
                'nullable',  // Optioneel voor nu, kan later verplicht worden
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Aangepaste validatieberichten (optioneel, maar gebruiksvriendelijker).
     */
    public function messages(): array
    {
        return [
            'categorie.required' => 'Selecteer alstublieft het type melding.',
            'categorie.in' => 'Het geselecteerde type melding is ongeldig.',

            'beschrijving.required' => 'Een beschrijving is verplicht.',
            'beschrijving.min' => 'De beschrijving moet minstens 20 karakters bevatten.',
            'beschrijving.max' => 'De beschrijving mag niet meer dan 2000 karakters bevatten.',

            'melding_datum_tijd.required' => 'De datum en tijd van het incident zijn verplicht.',
            'melding_datum_tijd.before_or_equal' => 'De datum kan niet in de toekomst liggen.',

            'aantal_vissen.integer' => 'Het aantal vissen moet een geheel getal zijn.',
            'aantal_vissen.max' => 'Het aantal vissen is onrealistisch hoog.',

            'melder_email.email' => 'Voer een geldig e-mailadres in.',

            'categorie_scope_geldig.required' => 'U moet bevestigen dat deze melding betrekking heeft op sportvisserij of dierenwelzijn.',

            'fotos.max' => 'U kunt maximaal 5 foto\'s uploaden.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                \Illuminate\Support\Facades\Log::error('OverlastMelding validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'data' => $this->all()
                ]);
            }
        });
    }
}
