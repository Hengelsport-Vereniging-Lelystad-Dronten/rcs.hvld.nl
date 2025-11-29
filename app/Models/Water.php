<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Water extends Model
{
    use HasFactory;

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
