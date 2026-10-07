<?php

namespace App\Enums\Visitor;

enum VisitorStatus: string
{
    case ARRIVE = 'Arrive';
    case DEPART = 'Depart';

    public function getLabel(): string
    {
        return match ($this) {
            self::ARRIVE => __('visitor.arrive'),
            self::DEPART => __('visitor.depart'),
        };
    }
}
