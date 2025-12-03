<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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