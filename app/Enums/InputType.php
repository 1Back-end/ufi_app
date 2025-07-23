<?php

namespace App\Enums;

enum InputType: string
{
    case TEXT = 'text';
    case SELECT = 'select';
    case COMMENT = 'comment';
    case NUMBER = 'number';
    case SELECT2 = 'select2';
    case VALEURDIRECT = 'valeur_direct';
    case INTERLINE = 'inline';
    case GROUPE = 'group';
}
