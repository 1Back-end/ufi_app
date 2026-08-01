<?php
namespace App\Enums;

enum StockAdjustmentAction: string
{
    case AVARIE           = 'avarie';
    case AJUSTEMENT_PLUS  = 'ajustement_plus';
    case AJUSTEMENT_MOINS = 'ajustement_moins';

    public function label(): string
    {
        return match ($this) {
            self::AVARIE           => 'Avarie',
            self::AJUSTEMENT_PLUS  => 'Augmentation',
            self::AJUSTEMENT_MOINS => 'Suppression',
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
