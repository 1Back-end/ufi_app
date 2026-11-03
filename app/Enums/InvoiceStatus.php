<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case BILLED = 'billed';
    case UNBILLED = 'unbilled';

    public function label(): string
    {
        return match($this) {
            self::BILLED => 'Facturé',
            self::UNBILLED => 'Non facturé',
        };
    }

    public static function safeLabel(self|string|null $value): string
    {
        if ($value instanceof self) {
            return $value->label();
        }

        return self::tryFrom((string) $value)?->label() ?? 'Inconnu';
    }

    public static function toArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
