<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model: ControleRonde
 *
 * Dit model representeert een controle-ronde uitgevoerd door een controleur op een specifiek water.
 * Het bevat metadata zoals start- en eindtijd, opmerkingen en de status van de ronde.
 * Relaties met `User`, `Water` en `Overtreding` zijn hieronder gedefinieerd.
 */
class ControleRonde extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'water_id',
        'start_tijd',
        'eind_tijd',
        'opmerkingen',
        'status',
    ];

    protected $casts = [
        'start_tijd' => 'datetime',
        'eind_tijd' => 'datetime',
    ];

    /**
     * Relatie: Een ControleRonde behoort toe aan één Gebruiker (Controleur).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relatie: Een ControleRonde behoort toe aan één Water.
     */
    public function water()
    {
        return $this->belongsTo(Water::class);
    }
    
    /**
     * Relatie: Een ControleRonde kan meerdere Overtredingen bevatten.
     */
    public function overtredingen()
    {
        return $this->hasMany(Overtreding::class);
    }
}