<?php

namespace App\Enums;

enum AntecedentType: string
{
    case FAMILIAL = 'familial';
    case PERSONNEL = 'personnel';

    public function label(): string
    {
        return match($this) {
            self::FAMILIAL => 'Antécédents Familiaux',
            self::PERSONNEL => 'Antécédents Personnels',
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
