<?php

namespace App\Enums;

enum RendezVousStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case CLOSED = 'closed';
    case NO_SHOW = 'no_show';
    case IN_PROGRESS = 'in_progress';
    case SCHEDULED = 'scheduled';
    case ON_DEMAND = 'on_demand';
    case SUITE_CONSULTATION = 'follow_up_consultation';
    case TAKEN_FOR_CONSULTATION = 'taken_for_consultation';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Planifié',
            self::INACTIVE => 'Inactif',
            self::CLOSED => 'Clôturé',
            self::NO_SHOW => 'Non honoré',
            self::IN_PROGRESS => 'Traitement en cours',
            self::TAKEN_FOR_CONSULTATION => 'Prises pour consultation',
            self::SCHEDULED => 'Rendez-vous programmée',
            self::ON_DEMAND => 'Au besoin / Sans rendez-vous',
            self::SUITE_CONSULTATION => 'Suite consultation',
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
