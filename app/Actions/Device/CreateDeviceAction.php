<?php

namespace App\Actions\Device;

use App\Exceptions\GeneralException;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Models\Device;
use Illuminate\Http\JsonResponse;

class CreateDeviceAction
{
    public function execute(ApiLoginRequest $request)
    {
        $device = Device::create($request->only([
            'user_id',
            'device_id',
            'fcm_token',
            'huawei_token',
            'package_name',
            'brand',
            'model',
        ]));

        if (! $device) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating device');
        }

        return $device;
    }
}
