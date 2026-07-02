<?php

namespace App\Enums;

enum TypeConsultEnum: string
{
    case INTERNE = 'Interne';
    case EXTERNE = 'Externe';
    case INTERNE_EXTERNE = 'Interne et Externe';
    case SUR_RENDEZ_VOUS = 'Sur rendez-vous';



    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
