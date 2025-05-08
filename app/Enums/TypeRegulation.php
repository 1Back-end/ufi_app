<?php

namespace App\Enums;

enum TypeRegulation: int
{
    case CLIENT = 1;
    case ASSURANCE = 2;
    case ASSOCIATE = 3;

    public static function labels(): array
    {
        return [
            self::CLIENT->value => 'Client',
            self::ASSURANCE->value => 'Prise en charge',
            self::ASSOCIATE->value => 'Associate',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value];
    }

}
