<?php

namespace App\Helpers;

use App\Models\Pet;

class PetIdGenerator
{
    public static function generate(Pet $pet): string
    {
        $house_unit = $pet->unit->unit_number;
        $category_id = 3;
        $type_id = $pet->getAttributes()['type'];
        $done = false;

        $total_pet = Pet::where('type', $type_id)->where('unit_id', $pet->unit_id)->count();

        $accept_string = [
            [
                'regex' => '/^[0-9\/]+$/',
                'accept' => '/',
            ],
            [
                'regex' => '/^[0-9\-]+$/',
                'accept' => '-',
            ],
        ];

        $pet_id = str_pad($pet->unit->residence_id, 5, '0', STR_PAD_LEFT);

        foreach ($accept_string as $keyString => $valueString) {
            if (preg_match($valueString['regex'], $house_unit)) {
                $done = true;
                $unit = explode($valueString['accept'], $house_unit, 2);

                if (count($unit) == 2) {
                    $output_array = explode($valueString['accept'], $house_unit, 2);
                    $pet_id .= str_pad($output_array[0], 3, '0', STR_PAD_LEFT);
                    $pet_id .= str_pad($output_array[1], 4, '0', STR_PAD_LEFT);
                }
            }
        }

        if (! $done) {
            $pet_id .= '0000000';
        }

        $pet_id .= str_pad($category_id, 2, '0', STR_PAD_LEFT).
                   str_pad($type_id, 2, '0', STR_PAD_LEFT).
                   str_pad($total_pet, 2, '0', STR_PAD_LEFT);

        return $pet_id;
    }
}
