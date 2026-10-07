<?php

namespace App\Enums\UserFamily;

enum Relationship: int
{
    case HUSBAND = 1;
    case WIFE = 2;
    case FATHER = 3;
    case MOTHER = 4;
    case BROTHER = 5;
    case SISTER = 6;
    case SON = 7;
    case DAUGHTER = 8;
    case RELATIVE = 9;
    case CO_HOME = 10;

    public function label(): string
    {
        return match ($this) {
            self::HUSBAND => __('user.husband'),
            self::WIFE => __('user.wife'),
            self::FATHER => __('user.father'),
            self::MOTHER => __('user.mother'),
            self::BROTHER => __('user.brother'),
            self::SISTER => __('user.sister'),
            self::SON => __('user.son'),
            self::DAUGHTER => __('user.daughter'),
            self::RELATIVE => __('user.relative'),
            self::CO_HOME => __('user.co_home'),
            default => 'N/A',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($r) => [$r->value => __('user.'.strtolower($r->name))])
            ->toArray();
    }
}
