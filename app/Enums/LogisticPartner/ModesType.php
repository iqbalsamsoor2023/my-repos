<?php

namespace App\Enums\LogisticPartner;

enum ModesType: int
{
    case Local = 1;
    case International = 2;

    /**
     * Get the label for the enum value.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Local => __('parcel.local'),
            self::International => __('parcel.international'),
        };
    }

    /**
     * Get an array of status options for dropdowns.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($type) => [
            $type->value => $type->getLabel(),
        ])->toArray();
    }
}
