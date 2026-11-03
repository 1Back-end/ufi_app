<?php

namespace App\Enums;

enum AppointmentType: string
{
    case SCHEDULED = 'scheduled';
    case ON_DEMAND = 'on_demand';
    case SUITE_CONSULTATION = 'follow_up_consultation';

    public function label(): string
    {
        return match($this) {
            self::SCHEDULED => 'Rendez-vous programmée',
            self::ON_DEMAND => 'Au besoin / Sans urgence',
            self::SUITE_CONSULTATION => 'Suite consultation',

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
