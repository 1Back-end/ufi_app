<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case BILLED = 'Facturé';
    case UNBILLED = 'Non facturé';

    public function label(): string
    {
        return match($this) {
            self::BILLED => 'Facturé',
            self::UNBILLED => 'Non facturé',
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
