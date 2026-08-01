<?php

namespace App\Enums;

enum RendezVousStatus: string
{
    case ACTIVE = 'Actif';
    case INACTIVE = 'Inactif';
    case CLOSED = 'Clos';
    case NO_SHOW = 'No show';
    case IN_PROGRESS = 'Traitement en cours';
    case TAKEN_FOR_CONSULTATION = 'Prises pour consultation';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Actif',
            self::INACTIVE => 'Inactif',
            self::CLOSED => 'Clos',
            self::NO_SHOW => 'No show',
            self::IN_PROGRESS => 'Traitement en cours',
            self::TAKEN_FOR_CONSULTATION => 'Prises pour consultation',
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
