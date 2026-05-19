<?php

namespace App\Enums;

enum PaymentAccountType: string
{
    case CONSULTANT = 'consultant';
    case SALAIRE = 'salary';
    case ASSURANCE = 'assurance';
    case CAISSE = 'caisse';
    public function label(): string
    {
        return match ($this) {
            self::CONSULTANT   => 'Compte consultant',
            self::SALAIRE     => 'Compte salaire',
            self::ASSURANCE   => 'Compte assurance',
            self::CAISSE      => 'Compte caisse',
        };
    }

    public static function safeLabel(?string $value): string
    {
        return self::tryFrom($value)?->label() ?? 'Inconnu';
    }

    public static function toArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
