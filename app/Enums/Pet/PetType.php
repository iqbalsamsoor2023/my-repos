<?php

namespace App\Enums\Pet;

enum PetType: int
{
    case CAT = 1;
    case DOG = 2;

    public function label(): string
    {
        return match ($this) {
            self::CAT => __('pet.cat'),
            self::DOG => __('pet.dog'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->toArray();
    }
}
