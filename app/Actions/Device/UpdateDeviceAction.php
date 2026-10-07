<?php

namespace App\Actions\Device;

use App\Exceptions\GeneralException;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Models\Device;
use Illuminate\Http\JsonResponse;

class UpdateDeviceAction
{
    public function execute(ApiLoginRequest $request, Device $device)
    {
        $device->update([
            'device_id' => $request->device_id,
            'fcm_token' => $request->fcm_token,
            'huawei_token' => $request->huawei_token,
            'package_name' => $request->package_name,
            'brand' => $request->brand,
            'model' => $request->model,
        ]);

        if (! $device) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating device');
        }

        return $device;
    }
}
