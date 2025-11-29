<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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