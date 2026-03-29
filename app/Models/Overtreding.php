<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
        'genomen_maatregel',
        'details',
        'vispas_ingenomen',
        'status',
        'annulatie_reden',
        'geannuleerd_door',
        'geannuleerd_op',
        'exported_at',
    ];

    protected $casts = [
        'locatie_details' => 'array',
        'geconstateerd_op' => 'datetime',
        'vispas_ingenomen' => 'boolean',
        'geannuleerd_op' => 'datetime',
        'exported_at' => 'datetime',
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

    public function scopeNietGeexporteerd(Builder $query): Builder
    {
        return $query->whereNull('exported_at');
    }

    public function scopeGeexporteerd(Builder $query): Builder
    {
        return $query->whereNotNull('exported_at');
    }
}
