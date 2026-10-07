<?php

namespace App\Helpers;

use App\Models\Parcel;
use Illuminate\Support\Facades\DB;

class ParcelIdGenerator
{
    public static function generate(Parcel $parcel): string
    {
        $date_check = date_format($parcel->created_at, 'Y-m-d');

        $query = DB::table('parcels')
            ->selectRaw('parcels.unit_id, units.residence_id, DATE(parcels.created_at) createddate')
            ->leftjoin('units', 'units.id', 'parcels.unit_id')
            ->where('parcels.created_at', 'LIKE', '%'.$date_check.'%')
            ->where('units.residence_id', $parcel->unit->residence_id)
            ->get();

        $code = str_pad($parcel->unit->residence_id, 5, '0', STR_PAD_LEFT);
        $code .= '-';
        $code .= str_pad($parcel->created_at->format('ymd'), 6, '0', STR_PAD_LEFT);
        $code .= '-';
        $code .= str_pad($query->count(), 3, '0', STR_PAD_LEFT);

        return $code;
    }
}
