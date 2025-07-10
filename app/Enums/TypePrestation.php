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
    case HOSPITALISATION = 6;

    public static function toArray(): array
    {
        return [
            self::ACTES->value => 'Actes',
            self::CONSULTATIONS->value => 'Consultations',
            self::SOINS->value => 'Soins',
            self::PRODUITS->value => 'Produits',
            self::LABORATOIR->value => 'Examen de laboratoire',
            self::HOSPITALISATION->value => 'Hospitalisation',
        ];
    }

    public static function label($value): string
    {
        return match ($value) {
            self::ACTES => 'Actes',
            self::CONSULTATIONS => 'Consultations',
            self::SOINS => 'Soins',
            self::PRODUITS => 'Produits',
            self::LABORATOIR => 'Laboratoire',
            self::HOSPITALISATION => 'Hospitalisation',
        };
    }
}
