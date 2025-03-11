<?php

namespace App\Enums;

enum StatusClient: int
{
    case INACTIVE = 0;
    case ACTIVE = 1;
    case ARCHIVE = 2;
}
