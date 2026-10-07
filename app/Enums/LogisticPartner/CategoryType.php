<?php

namespace App\Enums\LogisticPartner;

enum CategoryType: int
{
    case Courier = 1;
    case FoodDelivery = 2;

    /**
     * Get the label for the enum value.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Courier => __('parcel.courier'),
            self::FoodDelivery => __('parcel.food_delivery'),
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
