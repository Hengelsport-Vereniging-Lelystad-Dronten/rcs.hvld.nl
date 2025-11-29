<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Voeg HasFactory toe voor eenvoudige seeding en tests
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Importeer de BelongsToMany relatie

/**
 * Eloquent Model voor de 'strafmaten' tabel.
 * Dit model vertegenwoordigt een enkele strafmaat in de applicatie.
 * Het definieert de relatie met de database en de attributen die beheerd mogen worden.
 * * @property int $id
 * @property string|null $code               De korte, interne code voor de strafmaat (optioneel).
 * @property string $omschrijving            De volledige omschrijving van de strafmaat.
 * @property int $order_id                   De numerieke waarde voor de vaste sorteervolgorde (nieuw).
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Strafmaat extends Model
{
    // Gebruik de HasFactory trait om de aanmaak van dummy data (seeding) te vereenvoudigen.
    use HasFactory;

    /**
     * De tabel die bij dit model hoort.
     * Standaard is dit de meervoudsvorm van de modelnaam ('strafmaten').
     * @var string
     */
    protected $table = 'strafmaten';

    /**
     * De attributen die massaal kunnen worden toegewezen (mass assignment).
     * Dit is een beveiligingsmaatregel; alleen velden in deze array mogen 
     * in één keer via de 'create' of 'update' methoden gevuld worden.
     * @var array<int, string>
     */
    protected $fillable = [
        'code',         // De optionele korte code
        'omschrijving', // De verplichte omschrijving
        'order_id',     // De verplichte sorteervolgorde (nieuw)
    ];

    /**
     * De attributen die van de JSON-weergave moeten worden verborgen (bijv. wachtwoorden).
     * Voor dit model is er niets te verbergen.
     * @var array<int, string>
     */
    protected $hidden = [
        //
    ];
    
    /**
     * De attributen die naar specifieke types moeten worden gecast (omdat database types vaak generiek zijn).
     * @var array<string, string>
     */
    protected $casts = [
        //
    ];

    /**
     * Definieert de vele-op-vele relatie tussen Strafmaten en OvertredingTypes.
     * Een strafmaat kan aan meerdere overtreding types worden gekoppeld, en vice versa.
     * * * Opmerking: Dit gaat uit van het bestaan van een OvertredingType model en een pivot-tabel,
     * * bijvoorbeeld: 'overtreding_type_strafmaat'.
     * @return BelongsToMany
     */
    public function overtredingTypes(): BelongsToMany
    {
        // Vervang OvertredingType::class door het daadwerkelijke model dat uw overtreding types vertegenwoordigt.
        return $this->belongsToMany(OvertredingType::class, 'overtreding_type_strafmaat', 'strafmaat_id', 'overtreding_type_id');
    }
}