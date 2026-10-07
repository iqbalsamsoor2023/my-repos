<?php

namespace App\Helpers;

use App\Models\Unit;
use Exception;
use InvalidArgumentException;

class HomeIdGenerator
{
    private const MAX_ATTEMPTS = 10;

    public static function generate(Unit $unit): string
    {
        // Home ID: 10300503019xxxxx
        // 10 - Province
        // 30 - District
        // 05 - Subdistrict
        // 03019 - moobanID
        // xxxxx – 5 digit random number (unique)

        $residence = $unit->residence;
        if (! isset($residence)) {
            throw new InvalidArgumentException('Residence is not set');
        }

        if (! isset($residence->subdistrict)) {
            throw new InvalidArgumentException('Subdistrict is not set');
        }

        $subDistrictcode = str_pad($residence->subdistrict->code, 6, '0', STR_PAD_LEFT);
        $mooban = str_pad($residence->id, 5, '0', STR_PAD_LEFT);
        $prefix = $subDistrictcode.$mooban;

        $candidates = [];
        while (count($candidates) < self::MAX_ATTEMPTS) {
            $randomNumber = self::generateRandomNumber(5);
            $candidates[$randomNumber] = $prefix.$randomNumber;
        }
        $candidates = array_values($candidates);

        // Note: home_id is not a primary key; uniqueness is enforced at application level only
        $existing = Unit::withTrashed()
            ->whereIn('home_id', $candidates)
            ->pluck('home_id')
            ->toArray();

        // Return the first candidate that doesn't exist
        foreach ($candidates as $candidate) {
            if (! in_array($candidate, $existing, true)) {
                return $candidate;
            }
        }

        throw new Exception('Failed to generate a unique home ID code');
    }

    private static function generateRandomNumber(int $digits): string
    {
        return str_pad(mt_rand(0, 10 ** $digits - 1), $digits, '0', STR_PAD_LEFT);
    }
}
