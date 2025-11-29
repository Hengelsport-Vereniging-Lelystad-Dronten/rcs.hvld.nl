<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertredingType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'omschrijving',
        'default_strafmaat_id',
    ];

    /**
     * Relatie: Een OvertredingType kan meerdere Overtredingen hebben.
     */
    public function overtredingen()
    {
        return $this->hasMany(Overtreding::class);
    }
}
