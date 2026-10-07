<?php

namespace App\Enums\Visitor;

enum ArrivalType: int
{
    case DRIVE_IN = 1;
    case WALK_IN = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::DRIVE_IN => __('visitor.drive_in'),
            self::WALK_IN => __('visitor.walk_in'),
        };
    }
}
