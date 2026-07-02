<?php

namespace App\Enums;

enum PurchaseOrderType: string
{

    CASE INTERNAL = 'internal';
    CASE EXTERNAL = 'external';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Interne',
            self::EXTERNAL => 'Externe',
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
