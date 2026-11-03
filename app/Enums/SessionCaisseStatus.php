<?php

namespace App\Enums;

enum SessionCaisseStatus: string
{
    case OUVERTE = 'OUVERTE';
    case FERMEE = 'FERMEE';

    public static function label(string $value): string
    {
        return match ($value) {
            self::OUVERTE->value => 'Ouverte',
            self::FERMEE->value => 'Fermée',
            default => 'Inconnu',
        };
    }

    public static function safeLabel(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return self::label($value);
    }
}
