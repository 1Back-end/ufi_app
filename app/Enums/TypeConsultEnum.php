<?php

namespace App\Enums;

enum TypeConsultEnum: string
{
    case INTERNE = 'Interne';
    case EXTERNE = 'Externe';
    case INTERNE_EXTERNE = 'Interne et Externe';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
