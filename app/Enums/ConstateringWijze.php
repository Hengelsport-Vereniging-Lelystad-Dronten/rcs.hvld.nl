<?php

namespace App\Enums;

enum ConstateringWijze: string
{
    case VISUEEL = 'visueel';
    case MELDING = 'melding';
    case CONTROLE = 'controle';
    case METING = 'meting';

    /**
     * Geeft alle enum-waardes als een array terug, ideaal voor validatie.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
