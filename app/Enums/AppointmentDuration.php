<?php

namespace App\Enums;

enum AppointmentDuration: int
{
    case DEFAULT = 14;

    public static function value(): int
    {
        return self::DEFAULT->value;
    }
}
