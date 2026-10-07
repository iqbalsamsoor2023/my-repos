<?php

namespace App\Enums\UserReaction;

enum ReactionTypeEnum: int
{
    case REMOVE_REACTION = 0;
    case LIKE = 1;
    case READ = 2;
}
