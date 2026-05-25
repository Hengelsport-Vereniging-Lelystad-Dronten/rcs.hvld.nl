<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * Model: Overtreding
 *
 * Beschrijft een enkele overtreding die tijdens een controle-ronde is geregistreerd.
 * Bevat verwijzingen naar het type overtreding, de vispas (optioneel), de genomen maatregel
 * en eventuele aanvullende details.
 */
class Overtreding extends Model
{
    use HasFactory;

    public const STATUS_ACTIEF = 'actief';
    public const STATUS_GEANNULEERD = 'geannuleerd';
    public const STATUS_CONCEPT = 'concept';
    public const STATUS_DEMO = 'demo';

    protected $table = 'overtredingen';

    protected $fillable = [
        'controle_ronde_id',
        'overtreding_type_id',
        'locatie_details',
        'geconstateerd_op',
        'constatering_wijze',
        'aanleiding',
        'middel',
        'vispasnummer',
        'vispas_foto_path',
        'vispas_scan_confidence',
        'genomen_maatregel',
        'details',
        'vispas_ingenomen',
        'status',
        'annulatie_reden',
        'geannuleerd_door',
        'geannuleerd_op',
        'exported_at',
        'export_status',
    ];

    protected $casts = [
        'locatie_details' => 'array',
        'geconstateerd_op' => 'datetime',
        'vispas_ingenomen' => 'boolean',
        'geannuleerd_op' => 'datetime',
        'exported_at' => 'datetime',
    ];

    protected $appends = [
        'resolved_locatie',
        'resolved_locatie_naam',
        'vispas_foto_url',
    ];

    /**
     * Relatie: Een Overtreding behoort tot één ControleRonde.
     */
    public function controleRonde()
    {
        return $this->belongsTo(ControleRonde::class);
    }
    
    /**
     * Relatie: Een Overtreding heeft één OvertredingType (code).
     */
    public function overtredingType()
    {
        return $this->belongsTo(OvertredingType::class);
    }

    public function geannuleerdDoor()
    {
        return $this->belongsTo(User::class, 'geannuleerd_door');
    }

    public function scopeActief(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIEF);
    }

    public function scopeWithoutType00(Builder $query): Builder
    {
        return $query->whereHas('overtredingType', function ($query) {
            $query->where('code', '<>', '00');
        });
    }

    public function scopeOnlyType00(Builder $query): Builder
    {
        return $query->whereHas('overtredingType', function ($query) {
            $query->where('code', '00');
        });
    }

    public function scopeNietGeexporteerd(Builder $query): Builder
    {
        return $query->whereNull('exported_at')
                    ->where('export_status', 'wel_exporteren');
    }

    public function scopeGeexporteerd(Builder $query): Builder
    {
        return $query->whereNotNull('exported_at')
                    ->where('export_status', 'wel_exporteren');
    }

    public function scopeExportStatus(Builder $query, string $status): Builder
    {
        return $query->where('export_status', $status);
    }

    public function scopeVoorExport(Builder $query): Builder
    {
        return $query->where('export_status', 'wel_exporteren')
                    ->whereNull('exported_at');
    }

    public function getResolvedLocatieAttribute(): ?array
    {
        $locatieDetails = $this->locatie_details;

        if (is_array($locatieDetails) && !empty($locatieDetails)) {
            return $locatieDetails;
        }

        $water = $this->controleRonde?->water;

        if (!$water) {
            return null;
        }

        return array_filter([
            'type' => 'water',
            'id' => $water->id,
            'naam' => $water->naam,
            'water_naam' => $water->naam,
            'lat' => $water->latitude,
            'lon' => $water->longitude,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    public function getResolvedLocatieNaamAttribute(): string
    {
        $locatie = $this->resolved_locatie;

        if (!$locatie) {
            return 'Geen locatie';
        }

        return $locatie['naam']
            ?? $locatie['water_naam']
            ?? $locatie['adres']
            ?? $locatie['locatie_omschrijving']
            ?? $locatie['omschrijving']
            ?? 'Geen locatie';
    }

    public function getVispasFotoUrlAttribute(): ?string
    {
        if (!$this->vispas_foto_path) {
            return null;
        }

        return Storage::disk('public')->url($this->vispas_foto_path);
    }
}
