<?php

namespace App\Enums;

enum TypeVisitePrestation: string
{
    case MEDICALE = 'medical';
    case SYSTEMATIQUE = 'systematic';
    case EMBAUCHE = 'hiring';

    public static function toArray(): array
    {
        return [
            self::MEDICALE->value => 'Visite médicale',
            self::SYSTEMATIQUE->value => 'Visite systématique',
            self::EMBAUCHE->value => 'Visite d\'embauche',
        ];
    }

    public static function label($value): string
    {
        return match ($value) {
            self::MEDICALE->value => 'Visite médicale',
            self::SYSTEMATIQUE->value => 'Visite systématique',
            self::EMBAUCHE->value => 'Visite d\'embauche',
            default => 'Inconnu',
        };
    }
}
