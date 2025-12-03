<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// ESSENTIEEL: Importeer het Strafmaat model zodat de relaties werken.
use App\Models\Strafmaat; 
use App\Models\Overtreding; 

/**
 * OvertredingType Model
 * Vertegenwoordigt de verschillende soorten overtredingen (bijv. '10' = geen schriftelijke toestemming).
 * Wordt gebruikt door de RecidiveCheckService om de code om te zetten naar een interne ID.
 * Dit model koppelt direct aan de te hanteren strafmaten.
 */
class OvertredingType extends Model
{
    use HasFactory;
    
    // Voor de duidelijkheid expliciet de tabelnaam definiëren.
    protected $table = 'overtreding_types';

    protected $fillable = [
        'code',
        'omschrijving',
        'default_strafmaat_id',
        'recidive_strafmaat_id',
    ];

    /**
     * Relatie: De standaard strafmaat voor dit type overtreding (1-op-1).
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function defaultStrafmaat()
    {
        return $this->belongsTo(Strafmaat::class, 'default_strafmaat_id');
    }

    /**
     * Relatie: De strafmaat die wordt toegepast bij recidive (1-op-1).
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function recidiveStrafmaat()
    {
        return $this->belongsTo(Strafmaat::class, 'recidive_strafmaat_id');
    }

    /**
     * Relatie: Een OvertredingType kan meerdere Overtredingen hebben (1-op-veel).
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function overtredingen()
    {
        return $this->hasMany(Overtreding::class);
    }
}