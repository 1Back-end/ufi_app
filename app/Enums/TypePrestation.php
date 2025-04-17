<?php

namespace App\Enums;

use Illuminate\Support\Facades\Log;

enum TypePrestation: int
{
    case ACTES = 1;
    case CONSULTATIONS = 2;
    case SOINS = 3;
    case PRODUITS = 4;
    case LABORATOIR = 5;

    public static function toArray(): array
    {
        return [
            self::ACTES->value => 'Actes',
            self::CONSULTATIONS->value => 'Consultations',
            self::SOINS->value => 'Soins',
            self::PRODUITS->value => 'Produits',
            self::LABORATOIR->value => 'Laboratoire',
        ];
    }

    public static function label($value): string
    {
        return match ($value) {
            self::ACTES->value => 'Actes',
            self::CONSULTATIONS->value => 'Consultations',
            self::SOINS->value => 'Soins',
            self::PRODUITS->value => 'Produits',
            self::LABORATOIR->value => 'Laboratoire',
        };
    }
}
