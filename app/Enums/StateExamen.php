<?php

namespace App\Enums;

enum StateExamen: string
{
    case CREATED = 'created';
    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case CANCELLED = 'cancelled';
    case PRINTED = 'printed';
    case DELIVERED = 'delivered';
    case REMIS = 'remis';

    public static function label(): string | null
    {
        return match (self::class) {
            self::CREATED => 'Non disponible',
            self::PENDING => 'En attente de validation',
            self::VALIDATED => 'Validé',
            self::CANCELLED => 'Annulé',
            self::PRINTED => 'Imprimé',
            self::DELIVERED => 'Collecté par le client',
            self::REMIS => 'Résultat Remis',
            default => NULL,
        };
    }

    public static function validated()
    {
        return [
            self::VALIDATED->value,
            self::PRINTED->value,
            self::DELIVERED->value,
            self::REMIS->value,
        ];
    }
}
