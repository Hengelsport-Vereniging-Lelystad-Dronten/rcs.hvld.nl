<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nachtviszone extends Model
{
    protected $fillable = [
        'water_id',
        'boundary',
    ];

    public function water()
    {
        return $this->belongsTo(Water::class);
    }
}