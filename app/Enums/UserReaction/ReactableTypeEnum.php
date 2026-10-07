<?php

namespace App\Enums\UserReaction;

enum ReactableTypeEnum: int
{
    case ANNOUNCEMENT = 1;
    case EVENT = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::ANNOUNCEMENT => 'Announcement',
            self::EVENT => 'Event'
        };
    }
}
