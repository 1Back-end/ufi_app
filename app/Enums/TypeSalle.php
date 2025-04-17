<?php

namespace App\Enums;

enum TypeSalle: int
{
    case COMMUNE = 1;
    case TWO_BED = 2;
    case ONE_BED = 3;
    case HAUT_STANDING = 4;

    public static function toArray(): array
    {
        return [
            self::COMMUNE->value => 'Commune',
            self::TWO_BED->value => '2 Lits',
            self::ONE_BED->value => '1 Lit',
            self::HAUT_STANDING->value => 'Haut Standing',
        ];
    }


    /**
     * @throws \Exception
     */
    public static function labels($value): string
    {
        return match ($value) {
            self::COMMUNE->value => 'Commune',
            self::TWO_BED->value => '2 Lits',
            self::ONE_BED->value => '1 Lit',
            self::HAUT_STANDING->value => 'Haut Standing',
            default => throw new \Exception("Type de salle pas définit !")
        };
    }
}
