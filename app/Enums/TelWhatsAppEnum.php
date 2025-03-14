<?php

namespace App\Enums;

enum TelWhatsAppEnum: string
{
    case OUI = 'Oui';
    case NON = 'Non';

    public static function values(): array
    {
        return [self::OUI->value, self::NON->value];
    }
}
