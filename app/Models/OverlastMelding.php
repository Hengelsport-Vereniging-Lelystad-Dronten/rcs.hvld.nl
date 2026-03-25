<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * Model: OverlastMelding
 *
 * Representeert een publieke melding over sportvisserij en dierenwelzijn.
 * Dit model is bedoeld voor omwonenden en publiek om meldingen in te dienen
 * over zaken als:
 * - Vissterfte
 * - Onjuist gedrag vissers
 * - Dierenmishandeling (vis-gerelateerd)
 * - Illegale visserij
 * - Vervuiling met impact op vissen
 *
 * @property int $id
 * @property string $categorie Enum: vissterfte, onjuist_gedrag_vissers, etc.
 * @property string $beschrijving Gedetailleerde omschrijving (verplicht)
 * @property \Carbon\Carbon $melding_datum_tijd Moment van het incident
 * @property int|null $aantal_vissen Optioneel aantal betrokken vissen
 * @property string|null $ernst_situatie Ernst level (laag/midden/hoog)
 * @property string|null $locatie_adres Adres of nabij adres
 * @property array|null $locatie_details JSON met kaartgegevens
 * @property array|null $fotos JSON array met bestandsnamen
 * @property string|null $melder_naam Optionele naam
 * @property string|null $melder_email Optioneel email
 * @property string|null $melder_telefoon Optioneel telefoon
 * @property bool $melder_anoniem Of melder anoniem wil blijven
 * @property bool $categorie_scope_geldig Bevestiging checkbox
 * @property string|null $captcha_token CAPTCHA verificatietoken
 * @property string $status Enum: nieuw, in_behandeling, afgehandeld, afgewezen
 * @property string|null $interne_notities Notities voor intern beheer
 * @property string|null $afgewezen_reden Reden van afwijzing
 * @property int|null $verwerkt_door User ID van beheerder
 * @property \Carbon\Carbon|null $verwerkt_op Moment van verwerking
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at Soft delete timestamp
 */
class OverlastMelding extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'overlast_meldingen';

    protected $appends = [
        'latitude',
        'longitude',
        'locatie_omschrijving',
        'naam_aanmelder',
        'email_aanmelder',
        'telefoon_aanmelder',
        'foto_urls',
    ];

    /**
     * CONSTANTEN VOOR CATEGORIEËN
     */
    public const CATEGORIE_VISSTERFTE = 'vissterfte';
    public const CATEGORIE_ONJUIST_GEDRAG = 'onjuist_gedrag_vissers';
    public const CATEGORIE_DIERENMISHANDELING = 'dierenmishandeling';
    public const CATEGORIE_ILLEGALE_VISSERIJ = 'illegale_visserij';
    public const CATEGORIE_VERVUILING = 'vervuiling';
    public const CATEGORIE_OVERIG = 'overig';

    /**
     * CONSTANTEN VOOR STATUS
     */
    public const STATUS_NIEUW = 'nieuw';
    public const STATUS_IN_BEHANDELING = 'in_behandeling';
    public const STATUS_AFGEHANDELD = 'afgehandeld';
    public const STATUS_AFGEWEZEN = 'afgewezen';

    /**
     * CONSTANTEN VOOR ERNST
     */
    public const ERNST_LAAG = 'laag';
    public const ERNST_MIDDEN = 'midden';
    public const ERNST_HOOG = 'hoog';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'categorie',
        'beschrijving',
        'melding_datum_tijd',
        'aantal_vissen',
        'ernst_situatie',
        'locatie_adres',
        'locatie_details',
        'fotos',
        'melder_naam',
        'melder_email',
        'melder_telefoon',
        'melder_anoniem',
        'categorie_scope_geldig',
        'captcha_token',
        'status',
        'interne_notities',
        'afgewezen_reden',
        'verwerkt_door',
        'verwerkt_op',
    ];

    /**
     * Type casting for attributes
     */
    protected $casts = [
        'melding_datum_tijd' => 'datetime',
        'locatie_details' => 'array',
        'fotos' => 'array',
        'melder_anoniem' => 'boolean',
        'categorie_scope_geldig' => 'boolean',
        'verwerkt_op' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get all available categories
     */
    public static function categories(): array
    {
        return [
            self::CATEGORIE_VISSTERFTE,
            self::CATEGORIE_ONJUIST_GEDRAG,
            self::CATEGORIE_DIERENMISHANDELING,
            self::CATEGORIE_ILLEGALE_VISSERIJ,
            self::CATEGORIE_VERVUILING,
            self::CATEGORIE_OVERIG,
        ];
    }

    /**
     * Get category as human-readable label
     */
    public function getCategoryLabel(): string
    {
        return match ($this->categorie) {
            self::CATEGORIE_VISSTERFTE => 'Vissterfte',
            self::CATEGORIE_ONJUIST_GEDRAG => 'Onjuist gedrag vissers',
            self::CATEGORIE_DIERENMISHANDELING => 'Dierenmishandeling (vis-gerelateerd)',
            self::CATEGORIE_ILLEGALE_VISSERIJ => 'Illegale visserij',
            self::CATEGORIE_VERVUILING => 'Vervuiling met impact op vissen',
            self::CATEGORIE_OVERIG => 'Overig (binnen scope)',
            default => 'Onbekend',
        };
    }

    protected function getParsedLocatieDetails(): ?array
    {
        $details = $this->locatie_details;

        if (is_string($details)) {
            $decoded = json_decode($details, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $details = $decoded;
            } else {
                return null;
            }
        }

        if (is_array($details)) {
            // Handle cases from bad insert: nested quoted JSON string
            if (isset($details[0]) && is_string($details[0]) && ($inner = json_decode($details[0], true)) && is_array($inner)) {
                $details = $inner;
            }
            return $details;
        }

        return null;
    }

    public function getLatitudeAttribute(): ?float
    {
        $locatie = $this->getParsedLocatieDetails();
        if ($locatie && isset($locatie['latitude'])) {
            return floatval($locatie['latitude']);
        }

        return null;
    }

    public function getLongitudeAttribute(): ?float
    {
        $locatie = $this->getParsedLocatieDetails();
        if ($locatie && isset($locatie['longitude'])) {
            return floatval($locatie['longitude']);
        }

        return null;
    }

    public function getLocatieOmschrijvingAttribute(): ?string
    {
        if (!empty($this->locatie_adres)) {
            return $this->locatie_adres;
        }

        $locatie = $this->getParsedLocatieDetails();

        if ($locatie && !empty($locatie['address'])) {
            return $locatie['address'];
        }

        if ($locatie && isset($locatie['latitude'], $locatie['longitude'])) {
            return sprintf('Coördinaten: %.6f, %.6f', $locatie['latitude'], $locatie['longitude']);
        }

        return null;
    }

    public function getNaamAanmelderAttribute(): ?string
    {
        return $this->melder_naam;
    }

    public function getEmailAanmelderAttribute(): ?string
    {
        return $this->melder_email;
    }

    public function getTelefoonAanmelderAttribute(): ?string
    {
        return $this->melder_telefoon;
    }

    public function getFotoUrlsAttribute(): array
    {
        $fotos = $this->fotos;

        if (!is_array($fotos) || empty($fotos)) {
            return [];
        }

        return array_map(function ($foto) {
            if (empty($foto)) {
                return null;
            }

            // Controleer of het pad al een URL is
            if (filter_var($foto, FILTER_VALIDATE_URL)) {
                return $foto;
            }

            // Roep de publieke disk URL aan
            return Storage::disk('public')->url($foto);
        }, array_values($fotos));
    }

    /**
     * Get all available statuses
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_NIEUW,
            self::STATUS_IN_BEHANDELING,
            self::STATUS_AFGEHANDELD,
            self::STATUS_AFGEWEZEN,
        ];
    }

    /**
     * Get status as human-readable label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_NIEUW => 'Nieuw',
            self::STATUS_IN_BEHANDELING => 'In behandeling',
            self::STATUS_AFGEHANDELD => 'Afgehandeld',
            self::STATUS_AFGEWEZEN => 'Afgewezen',
            default => 'Onbekend',
        };
    }

    /**
     * RELATIES
     */

    /**
     * De beheerder die deze melding heeft verwerkt
     */
    public function verwerktDoor()
    {
        return $this->belongsTo(User::class, 'verwerkt_door');
    }

    /**
     * SCOPES
     */

    /**
     * Scope: Alleen meldingen met geldige scope
     */
    public function scopeGelidgeBereik(Builder $query): Builder
    {
        return $query->where('categorie_scope_geldig', true);
    }

    /**
     * Scope: Alleen meldingen met ongeldige scope (voor afwijzing)
     */
    public function scopeOngelidgeBereik(Builder $query): Builder
    {
        return $query->where('categorie_scope_geldig', false);
    }

    /**
     * Scope: Meldingen die nog niet verwerkt zijn
     */
    public function scopeOnverwerkt(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NIEUW);
    }

    /**
     * Scope: Meldingen in behandeling
     */
    public function scopeInBehandeling(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_BEHANDELING);
    }

    /**
     * Scope: Afgehandelde meldingen
     */
    public function scopeAfgehandeld(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AFGEHANDELD);
    }

    /**
     * Scope: Afgewezen meldingen
     */
    public function scopeAfgewezen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AFGEWEZEN);
    }

    /**
     * Scope: Filter op categorie
     */
    public function scopeByCategorie(Builder $query, string $categorie): Builder
    {
        return $query->where('categorie', $categorie);
    }

    /**
     * Scope: Filter op datum range
     */
    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * HELPERS
     */

    /**
     * Markeer melding als in behandeling
     */
    public function markAsInBehandeling(User $user = null): static
    {
        $this->status = self::STATUS_IN_BEHANDELING;
        if ($user) {
            $this->verwerkt_door = $user->id;
            $this->verwerkt_op = now();
        }
        $this->save();
        return $this;
    }

    /**
     * Markeer melding als afgehandeld
     */
    public function markAsAfgehandeld(User $user = null): static
    {
        $this->status = self::STATUS_AFGEHANDELD;
        if ($user) {
            $this->verwerkt_door = $user->id;
            $this->verwerkt_op = now();
        }
        $this->save();
        return $this;
    }

    /**
     * Wijs melding af met optionele reden
     */
    public function reject(User $user, string $reason = null): static
    {
        $this->status = self::STATUS_AFGEWEZEN;
        $this->afgewezen_reden = $reason;
        $this->verwerkt_door = $user->id;
        $this->verwerkt_op = now();
        $this->save();
        return $this;
    }

    /**
     * Check of melding geldig is (geldige scope + bevestiging)
     */
    public function isValid(): bool
    {
        return $this->categorie_scope_geldig === true
            && in_array($this->categorie, self::categories());
    }

    /**
     * Check of melding anoniem moet blijven
     */
    public function isAnonym(): bool
    {
        return $this->melder_anoniem === true;
    }

    /**
     * Get melding contact info (respecteert anonimiteit)
     */
    public function getContactInfo(): array
    {
        if ($this->isAnonym()) {
            return [
                'naam' => 'Anoniem',
                'email' => null,
                'telefoon' => null,
            ];
        }

        return [
            'naam' => $this->melder_naam ?? 'Niet opgegeven',
            'email' => $this->melder_email,
            'telefoon' => $this->melder_telefoon,
        ];
    }
}
