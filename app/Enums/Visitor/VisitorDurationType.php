<?php

namespace App\Enums\Visitor;

enum VisitorDurationType: int
{
    case LIMITED_TIME = 1;
    case ONE_TIME = 0;

    public function getLabel(): string
    {
        return match ($this) {
            self::LIMITED_TIME => __('visitor.limited_time'),
            self::ONE_TIME => __('visitor.one_time'),
        };
    }
}
