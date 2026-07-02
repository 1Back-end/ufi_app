<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case PARTIALLY_RECEIVED = 'partially_received';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';
    case IN_PROGRESS = 'in_progress';
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PENDING => 'En attente',
            self::APPROVED => 'Approuvé',
            self::PARTIALLY_RECEIVED => 'Partiellement reçu',
            self::IN_PROGRESS => "Validée",
            self::RECEIVED => 'Reçu',
            self::CANCELLED => 'Annulée',
            self::REJECTED => 'Rejetée',
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
