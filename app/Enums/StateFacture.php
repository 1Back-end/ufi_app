<?php

namespace App\Enums;


enum StateFacture: int
{
    case CREATE = 1;
    case IN_PROGRESS = 2;
    case PAID = 3;
    case CANCELLED = 4;

     public static function name(self $value): string
     {
         return match ($value) {
             self::CREATE => 'Créer',
             self::IN_PROGRESS => 'En cours',
             self::PAID => 'Soldé',
             self::CANCELLED => 'Annulé',
         };
     }

     /*public static function toArray(): array
     {
         return [
             self::CREATE->value => self::name(self::CREATE),
             self::IN_PROGRESS->value => self::name(self::IN_PROGRESS),
             self::PAID->value => self::name(self::PAID),
             self::CANCELLED->value => self::name(self::CANCELLED),,
         ];
     }*/
}
