<?php

namespace App\Helpers;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;

class VehicleNumberGenerator
{
    public static function generate(Vehicle $vehicle): string
    {
        $residence = $vehicle->unit->residence->id;
        $unit = $vehicle->unit->unit_number;
        $categoryId = 2;
        $type = $vehicle->vehicleModel->type;

        $count = $vehicle->whereHas('vehicleModel', function (Builder $query) use ($type) {
            $query->where('type', $type);
        })
            ->where('unit_id', $vehicle->unit_id)
            ->count();

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

        $vehicleGeneratedNumber = '';
        $vehicleGeneratedNumber = str_pad($residence, 5, '0', STR_PAD_LEFT);
        $done = 0;

        foreach ($accept_string as $keyString => $valueString) {
            if (preg_match($valueString['regex'], $unit)) {
                $done = 1;
                $value = explode($valueString['accept'], $unit, 2);

                if (count($value) == 2) {
                    $output_array = explode($valueString['accept'], $unit, 2);
                    $vehicleGeneratedNumber .= str_pad($output_array[0], 3, '0', STR_PAD_LEFT);
                    $vehicleGeneratedNumber .= str_pad($output_array[1], 4, '0', STR_PAD_LEFT);
                }
            }
        }
        if ($done == 0) {
            $vehicleGeneratedNumber .= '0000000';
        }
        $vehicleGeneratedNumber .= str_pad($categoryId, 2, '0', STR_PAD_LEFT);
        $vehicleGeneratedNumber .= str_pad($type, 2, '0', STR_PAD_LEFT);
        $vehicleGeneratedNumber .= str_pad($count, 2, '0', STR_PAD_LEFT);

        return $vehicleGeneratedNumber;
    }
}
