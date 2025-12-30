<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Water extends Model
{
    use HasFactory;

    /**
     * Model: Water
     *
     * Vertegenwoordigt een viswater in het systeem. Bevat naam en eventuele metadata.
     * Wordt gebruikt bij het aanmaken van controle-rondes en in dropdowns in de UI.
     */

    // Deze velden mogen via mass-assignment (create/update) worden gevuld.
    protected $fillable = [
        'naam',
        'type',
        'beheersgebied',
        'beschrijving',
        'latitude',
        'longitude',
        'boundary',
        'is_verboden',
        'default_overtreding_type_id',
    ];

    /**
     * De attributen die naar specifieke types moeten worden gecast.
     */
    protected $casts = [
        'boundary' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_verboden' => 'boolean',
    ];

    /**
     * Relatie: Een Water kan meerdere ControleRondes hebben.
     */
    public function controleRondes()
    {
        return $this->hasMany(ControleRonde::class);
    }

    /**
     * Relatie: Een Water kan een standaard overtredingstype hebben (bijv. voor verboden wateren).
     */
    public function defaultOvertredingType()
    {
        return $this->belongsTo(OvertredingType::class, 'default_overtreding_type_id');
    }

    /**
     * Relatie: Een Water kan meerdere Nachtviszones hebben.
     */
    public function nachtviszones()
    {
        return $this->hasMany(Nachtviszone::class);
    }
}
