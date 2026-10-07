<?php

namespace App\Actions\Parcel;

use App\Models\Parcel;

class GetParcelAction
{
    public function execute($request)
    {
        $parcel = Parcel::with('unit', 'unit.residence', 'courier', 'receiver', 'media');

        if (isset($request->qr_code)) {
            $parcel = $parcel->where('qr_code', $request->qr_code);
        }

        if (isset($request->unit_id)) {
            $parcel = $parcel->where('unit_id', $request->unit_id);
        }

        if (isset($request->status)) {
            $parcel = $parcel->where('status', $request->status);
        }

        if (isset($request->residence_id)) {
            $parcel = $parcel->whereHas('unit.residence', function ($query) use ($request) {
                return $query->where('id', $request->residence_id);
            });
        }

        if (isset($request->receiver_name)) {
            $parcel = $parcel->where('receiver_name', $request->receiver_name)->orWhere('receiver_name', 'All');
        }

        if (isset($request->user_id)) {
            $parcel = $parcel->whereHas('unit.residence', function ($query) use ($request) {
                return $query->where('property_management_user_id', $request->user_id);
            });
        }

        if (isset($request->receiver_name) && isset($request->unit_id)) {
            $parcel = $parcel->where(function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('receiver_name', $request->receiver_name)
                      ->where('unit_id', $request->unit_id);
                })
                ->orWhere(function ($q) use ($request) {
                    $q->where('receiver_name', 'All')
                      ->where('unit_id', $request->unit_id);
                });
            });
        }

        return $parcel->orderBy('id', 'DESC')->paginate(20);
    }
}
