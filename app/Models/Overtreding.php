<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected $table = 'overtredingen';

    protected $fillable = [
        'controle_ronde_id',
        'overtreding_type_id',
        'vispasnummer',
        'genomen_maatregel',
        'details',
        'vispas_ingenomen',
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
}