<?php

use Illuminate\Database\Eloquent\Model;
use App\Enums\FacilityAndAmenity\DayOfWeekEnum;
use App\Enums\Residence\SubType;
use App\Enums\Unit\HouseType;
use App\Enums\User\RoleType;
use App\Models\Company;
use App\Models\Erp\CdpCompany;
use App\Models\Erp\ThailandDistrict;
use App\Models\Erp\ThailandProvince;
use App\Models\Sgoc\Checkpoint;
use App\Models\Residence;
use App\Models\Sgoc\Round;
use Carbon\Carbon;
use Illuminate\Support\Arr;

/**
 * Function to get residence of PM role by user id
 *
 * @param $user_id
 * @return Model
 */
if (! function_exists('get_residence_by_property_management')) {
    function get_residence_by_property_management($user_id = null)
    {
        return Residence::where('property_management_user_id', $user_id)->first();
    }
}

/**
 * Function to get residence of PM role by user id
 *
 * @param $user_id
 * @return Model
 */
if (! function_exists('get_residence_by_property_management_operation_center')) {
    function get_residence_by_property_management_operation_center($user_id = null)
    {
        $company = CdpCompany::where('mmb_user_id', $user_id)->first();

        $residences = Residence::where('property_management_id', $company->id)->get();

        $residence_ids = [];

        foreach ($residences as $residences) {
            $residence_ids[] = $residences->id;
        }

        return $residence_ids;
    }
}

/**
 * Function to get residence of Developer role by user id
 *
 * @param $user_id
 * @return Model
 */
if (! function_exists('get_residence_by_developer')) {
    function get_residence_by_developer($user_id = null)
    {
        return Residence::where('developer_user_id', $user_id)->pluck('id');
    }
}

/**
 * Function to get residence of RTM role by user id
 *
 * @param $user_id
 * @return Model
 */
if (! function_exists('get_residence_id_list_by_rtm')) {
    function get_residence_id_list_by_rtm($user_id = null)
    {
        return Residence::where('rtm_user_id', $user_id)->pluck('id');
    }
}

/**
 * Function to get list of province by role
 *
 * @return Arr
 */
if (! function_exists('list_provinces')) {
    function list_provinces()
    {
        $list = [];
        $provinceIds = [];

        $user = auth()->user();

        if ($user->hasRole('Property Management Operation Center')) {
            $company = Company::where('user_id', $user->id)->first();

            $provinceIds = Residence::where('property_management_id', $company->id)->with('subdistrict.district')->get()->pluck('subdistrict.district.province_id');

            $models = ThailandProvince::whereIn('id', $provinceIds)->get();
        } else {
            $models = ThailandProvince::get();
        }

        foreach ($models as $model) {
            $list[$model->id] = $model->code.' - '.$model->name_in_english;
        }

        return Arr::sortRecursive($list);
    }
}

/**
 * Function to get list of districts by role
 *
 * @return Arr
 */
if (! function_exists('list_districts')) {
    function list_districts()
    {
        $list = [];
        $districtIds = [];

        $user = auth()->user();

        if ($user->hasRole('Property Management Operation Center')) {
            $company = Company::where('user_id', $user->id)->first();

            $districtIds = array_unique(
                Residence::query()->where('property_management_id', $company->id)
                    ->with('subdistrict')->get()
                    ->pluck('subdistrict.district_id')
                    ->toArray()
            );

            $models = ThailandDistrict::whereIn('id', $districtIds)->get();
        } else {
            $models = ThailandDistrict::get();
        }

        foreach ($models as $model) {
            $list[$model->id] = $model->code.' - '.$model->name_in_english;
        }

        return Arr::sortRecursive($list);
    }
}

/**
 * Function to get list of residence by role
 *
 * @param $user_id
 * @return Model
 */
if (! function_exists('list_residences')) {
    function list_residences()
    {
        $user = auth()->user();
        $list = [];

        $query = Residence::query();

        if ($user->hasRole('Developer')) {
            $developer = CdpCompany::where('mmb_user_id', $user->id)->first();

            $query->where('developer_id', $developer->id);
        } elseif ($user->hasRole('Property Management')) {
            $query->where('property_management_user_id', $user->id);
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);
            $query->whereIn('id', $residence_ids);
        } elseif ($user->hasRole('Re-sales & Tenancy Management')) {
            $query->where('rtm_user_id', $user->id);
        } elseif ($user->hasRole('Sales Management')) {
            $query->where('sales_management_user_id', $user->id);
        }

        // Only the three attributes the loop reads. Some roles (Super Admin, Admin)
        // match every residence, and hydrating ~12k models in full costs around 60MB per call.
        $residences = $query->select('id', 'name', 'name_th')->get();
        foreach ($residences as $residence) {
            $list[$residence->id] = "{$residence->name} ({$residence->name_th})";
        }

        return Arr::sortRecursive($list);
    }
}

if (! function_exists('applyResidenceRoleFilter')) {
    function applyResidenceRoleFilter($query)
    {
        $user = auth()->user();

        if ($user->hasRole('Developer')) {
            $developerId = CdpCompany::where('mmb_user_id', $user->id)->value('id');
            $query->where('developer_id', $developerId);

        } elseif ($user->hasRole('Property Management')) {
            $query->where('property_management_user_id', $user->id);

        } elseif ($user->hasRole('Property Management Operation Center')) {
            $ids = get_residence_by_property_management_operation_center($user->id);
            $query->whereIn('id', $ids);

        } elseif ($user->hasRole('Re-sales & Tenancy Management')) {
            $query->where('rtm_user_id', $user->id);

        } elseif ($user->hasRole('Sales Management')) {
            $query->where('sales_management_user_id', $user->id);
        }

        return $query;
    }
}

if (! function_exists('list_create_residences')) {
    function list_create_residences()
    {
        $user = auth()->user();
        $list = [];

        $query = Residence::query();

        // will be use if issue arise where Admin update any mooban that causing problem
        // if ($user->hasRole('Admin')) {
        //     $query->whereIn('residence_activation_status_id', [1, 6]);
        if ($user->hasRole('Developer')) {
            $developer = CdpCompany::where('mmb_user_id', $user->id)->first();

            $query->where('developer_id', $developer->id);
        } elseif ($user->hasRole('Property Management')) {
            $query->where('property_management_user_id', $user->id);
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);
            $query->whereIn('id', $residence_ids);
        }

        $residences = $query->get();
        foreach ($residences as $residence) {
            $list[$residence->id] = "{$residence->name} ({$residence->name_th})";
        }

        return Arr::sortRecursive($list);
    }
}

/**
 * Function to get list of checkpoint by role
 *
 * @param $user_id
 * @return Model
 */
if (! function_exists('list_checkpoints')) {
    function list_checkpoints($residence_id = null)
    {
        $user = auth()->user();
        $checkpoints = Checkpoint::query();

        if ($user->hasAnyRole(['Developer', 'Property Management', 'Property Management Operation Center', 'Super Admin', 'Admin'])) {
            $checkpoints = $checkpoints->where('mmb_residence_id', $residence_id);
        }

        $checkpoints = $checkpoints->get();

        foreach ($checkpoints as $checkpoint) {
            $list[$checkpoint->id] = $checkpoint->name;
        }

        if (isset($list)) {
            return Arr::sortRecursive($list);
        } else {
            return [];
        }
    }
}

/**
 * Function to get Y-m-d H:i:s format date
 *
 * @param  $date
 * @return string
 */
if (! function_exists('save_date')) {
    function save_date($date)
    {
        $new_format_date = null;

        if ($date) {
            $new_format_date = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
        }

        return $new_format_date;
    }
}

/**
 * Function to get toggle table column that return true
 *
 * @param  $date
 * @return string
 */
if (! function_exists('getTrueKeys')) {
    function getTrueKeys(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $result = array_merge($result, getTrueKeys($value, $fullKey));
            } elseif ($value === true) {
                $result[] = $fullKey;
            }
        }

        return $result;
    }
}
/**
 * Function to get residence of SM role by user id
 *
 * @param $user_id
 * @return Model
 */
if (! function_exists('get_residence_id_list_by_sm')) {
    function get_residence_id_list_by_sm($user_id = null)
    {
        return Residence::where('sales_management_user_id', $user_id)->pluck('id');
    }
}

/**
 * Set house type options for a form select, depending on property_type/sub_type.
 */
function setHouseTypeOptions($state, callable $set): void
{
    if (! $state) {
        $set('house_type_options', []);

        return;
    }

    $subType = SubType::tryFrom($state);
    if (! $subType) {
        $set('house_type_options', []);

        return;
    }

    $options = collect(HouseType::bySubType($subType))
        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
        ->toArray();

    $set('house_type_options', $options);
}

    /**
     * Use in data visualisation for percentage decimal places
     */
    function formatPercentageForStatOverview(float $percentage): string
    {
        return number_format($percentage, 2);
    }

/**
 * Convert a day string or numeric day representation to its integer value.
 *
 * If the input is numeric (or numeric string), it returns it as an integer.
 * Otherwise, it converts a day label (e.g., "Monday") to its corresponding integer value using DayOfWeekEnum.
 *
 * @param  string|int  $day  The day to convert (e.g., "Monday" or 1)
 * @return int|null The integer value of the day, or null if conversion fails
 */
if (! function_exists('convertDay')) {
    function convertDay($day): ?int
    {
        if (is_numeric($day)) {
            return (int) $day; // convert numeric string to int
        }

        // Convert label string to enum int
        $enum = DayOfWeekEnum::fromLabel($day);

        return $enum?->value ?? null;
    }
}

/**
 * Function to get default residence id by user
 *
 * @return int|null
 */
if (! function_exists('get_default_residence_id_by_user')) {
    function get_default_residence_id_by_user()
    {
        $defaultResidenceId = null;
        $user = auth()->user();
        if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $residence = get_residence_by_property_management($user->id);
            if ($residence) {
                $defaultResidenceId = $residence->id;
            }
        }

        return $defaultResidenceId;
    }
}

/**
 * Function to get list of zone by residence
 *
 * @return array
 */
if (! function_exists('list_rounds')) {
    function list_rounds($residenceId = null)
    {
        $roundQuery = Round::query();

        if ($residenceId) {
            $roundQuery = $roundQuery->where('mmb_residence_id', $residenceId);
        }

        $rounds = $roundQuery->get();
        $list = [];

        foreach ($rounds as $round) {
            $list[$round->id] = "{$round->round_number} ({$round->start_time} - {$round->end_time})";
        }

        if (!empty($list)) {
            return Arr::sortRecursive($list);
        } else {
            return [];
        }
    }
}
/**
 * Convert a date's Gregorian year to the Thai Buddhist year (+543).
 *
 * @param  \Carbon\Carbon|\DateTimeInterface|string  $date
 */
if (! function_exists('buddhist_year')) {
    function buddhist_year(Carbon|\DateTimeInterface|string $date): int
    {
        return Carbon::parse($date)->year + 543;
    }
}

/**
 * Format a date in Thai with a short month and Buddhist year, e.g. "05 มิ.ย. 2569".
 *
 * @param  \Carbon\Carbon|\DateTimeInterface|string  $date
 */
if (! function_exists('thai_date')) {
    function thai_date(Carbon|\DateTimeInterface|string $date, string $format = 'd M', bool $displayTime = false): string
    {
        $date = Carbon::parse($date);

        $formatted = $date->locale('th')->translatedFormat($format).' '.buddhist_year($date);

        return $displayTime ? $formatted.' '.$date->format('H:i:s') : $formatted;
    }
}

/**
 * Locale-aware date/time formatter.
 *
 * Thai locale → Buddhist-year Thai date via thai_date(); any other locale → the
 * given Gregorian $format.
 *
 * @param  \Carbon\Carbon|\DateTimeInterface|string|null  $date
 * @param  string  $format  Gregorian format used for non-Thai locales.
 * @param  bool  $withTime  Append a time component in both locales.
 * @param  string  $timeFormat  Time format appended for non-Thai locales when $withTime.
 * @param  string|null  $placeholder  Returned when $date is empty.
 */
if (! function_exists('localize_date_time')) {
    function localize_date_time(
        Carbon|\DateTimeInterface|string|null $date,
        string $format = 'd-M-y',
        bool $withTime = false,
        string $timeFormat = 'H:i:s',
        ?string $placeholder = '-',
    ): ?string {
        if (blank($date)) {
            return $placeholder;
        }

        $date = Carbon::parse($date);

        if (app()->getLocale() === 'th') {
            return thai_date($date, displayTime: $withTime);
        }

        return $date->format($withTime ? "{$format} {$timeFormat}" : $format);
    }
}
