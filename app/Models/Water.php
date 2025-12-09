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
    ];

    /**
     * Relatie: Een Water kan meerdere ControleRondes hebben.
     */
    public function controleRondes()
    {
        return $this->hasMany(ControleRonde::class);
    }
}
