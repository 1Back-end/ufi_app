<?php

namespace App\Enums;

enum DeliveryChannelEnum: string
{
    case EMAIL = 'email';
    case WHATSAPP = 'whatsapp';
    case PRESENTIEL = 'presentiel';
    case SMS = 'sms';

    public function label(): string
    {
        return match($this) {
            self::EMAIL => 'Email',
            self::WHATSAPP => 'WhatsApp',
            self::PRESENTIEL => 'Présentiel',
            self::SMS => 'SMS',
        };
    }
}
