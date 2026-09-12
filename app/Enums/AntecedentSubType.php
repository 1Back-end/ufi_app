<?php

namespace App\Enums;

enum AntecedentSubType:string
{
    case PERSONNEL_MEDICAL = 'personnel_medical';
    case PERSONNEL_CHIRURGICAL = 'personnel_chirurgical';

    public function label(): string
    {
        return match($this) {
            self::PERSONNEL_MEDICAL => 'Personnels Médicaux',
            self::PERSONNEL_CHIRURGICAL => 'Personnels Chirurgicaux',
        };
    }

    public static function array(): array
    {
        return [
            self::PERSONNEL_MEDICAL->value => self::PERSONNEL_MEDICAL->label(),
            self::PERSONNEL_CHIRURGICAL->value => self::PERSONNEL_CHIRURGICAL->label(),
        ];
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
