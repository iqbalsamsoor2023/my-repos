<?php

namespace App\Enums\User;

enum Gender: int
{
    case MALE = 1;
    case FEMALE = 2;

    public function label(): string
    {
        return match ($this) {
            self::MALE => __('user.male'),
            self::FEMALE => __('user.female'),
        };
    }
}
