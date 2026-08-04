<?php
namespace App\Enums;

enum StockAdjustmentAction: string
{
    case AVARIE           = 'avarie';
    case AJUSTEMENT_PLUS  = 'ajustement_plus';

    public function label(): string
    {
        return match ($this) {
            self::AVARIE           => 'Avarie',
            self::AJUSTEMENT_PLUS  => 'Augmentation',
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
