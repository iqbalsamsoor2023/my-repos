<?php

namespace App\Helpers;

use App\Enums\Residence\MoobanType;
use App\Enums\Residence\SubType;
use App\Enums\Unit\PmocType;
use App\Enums\Unit\PropertyType;
use App\Enums\Unit\ResidencePropertyType;
use App\Models\Unit;

class MmbIdGenerator
{
    public static function execute(Unit $model)
    {
        // 00011 00012 01 02 03 04
        // MMB ID: 00011 00012 01 02 03 04
        // 00011 - moobanID
        // 00012 - home id
        // 01 - moobanType (Public - 01, Residence - 02, Factory - 03, PMOC - 04)
        // 02 - unitType (PropertyType, ResidencePropertyType, PmocType, FactoryType)
        // 03 - type id (1 represent owner & 2 represent tenant)
        // 04 - number of resident who registered for the house unit (following by sequence)

        $unitUsers = $model->unitUsers->sortBy('id');
        $userWithGeneratedMMBId = $unitUsers->map(function ($unitUser, $key) {
            $isOwner = $unitUser->is_owner;
            $residence = $unitUser->unit->residence;
            $unit = $unitUser->unit;
            $moobanId = str_pad($residence->id, 5, '0', STR_PAD_LEFT);
            $unitRandomDigits = str_pad(substr($unit->home_id, 11, 5), 5, '0', STR_PAD_LEFT);

            $moobanType = $unitUser->unit->residence->mooban_type;
            $subType = $unitUser->unit->property_type ?? 0;
            $pmocType = $unitUser->unit->residence->sub_type ?? 0;

            if ($moobanType == MoobanType::PUBLIC->value) {
                $categoryId = '01'.self::getCategoryByUnitSubType($subType);
            } elseif ($moobanType == MoobanType::RESIDENCE->value) {
                $categoryId = '02'.self::getCategoryByResidencePropertyType($subType);
            } elseif ($moobanType == MoobanType::FACTORY->value) {
                $categoryId = '03'.self::getCategoryByFactoryPropertyType();
            } elseif ($moobanType == MoobanType::PMOC->value) {
                $categoryId = '04'.self::getCategoryByPmocType($pmocType);
            } else {
                $categoryId = '0000';
            }

            $userType = $isOwner ? 1 : 2; // 1 represents owner, 2 represents tenant

            $sequenceNumber = $key + 1;
            $mmbIdCode = $moobanId.$unitRandomDigits.$categoryId.'0'.$userType.str_pad($sequenceNumber, 2, '0', STR_PAD_LEFT);

            if ($unitUser->mmb_id !== $mmbIdCode) {
                $unitUser->mmb_id = $mmbIdCode;
                $unitUser->save();
            }

            return $unitUser;
        });

        $latestUnitUser = $userWithGeneratedMMBId->last();

        if (! is_null($latestUnitUser)) {
            return $latestUnitUser;
        }
    }

    private static function getCategoryByUnitSubType(int $subType): string
    {
        switch ($subType) {
            case SubType::POOL_VILLA->value:
                return '01';
            case SubType::SINGLE_HOME->value:
                return '02';
            case SubType::TWIN_HOME->value:
                return '03';
            case SubType::TOWN_HOME->value:
                return '04';
            case SubType::CONDO_HIGH_RISE->value:
                return '05';
            case SubType::CONDO_LOW_RISE->value:
                return '06';
            default:
                return '00';
        }
    }

    private static function getCategoryByFactoryPropertyType(): string
    {
        return '01';
    }

    private static function getCategoryByResidencePropertyType(int $propertyType): string
    {
        switch ($propertyType) {
            case ResidencePropertyType::SERVICE_APARTMENT->value:
                return '01';
            default:
                return '00';
        }
    }

    private static function getCategoryByPmocType(int $pmocType): string
    {
        switch ($pmocType) {
            case PmocType::ART_GALLERY->value:
                return '01';
            case PmocType::COMMUNITY_MALL->value:
                return '02';
            case PmocType::HOSPITAL->value:
                return '03';
            case PmocType::HOTEL->value:
                return '04';
            case PmocType::OFFICE_BUILDING->value:
                return '05';
            case PmocType::RELIGIOUS_ORGANIZATION->value:
                return '06';
            case PmocType::SCHOOL->value:
                return '07';
            case PmocType::SHOPPING_MALL->value:
                return '08';
            case PmocType::SHOWROOM->value:
                return '09';
            case PmocType::SPORT_CLUB->value:
                return '10';
            default:
                return '00';
        }
    }
}
