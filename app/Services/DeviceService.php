<?php

namespace App\Services;

use App\Actions\Device\CreateDeviceAction;
use App\Actions\Device\UpdateDeviceAction;
use App\Exceptions\GeneralException;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Http\Requests\Device\RemoveDeviceRequest;
use App\Models\Device;
use Illuminate\Http\JsonResponse;

class DeviceService
{
    public function deviceChecker(ApiLoginRequest $request)
    {
        if (is_null($request->huawei_token) == false) {
            $checkDeviceExist = Device::where('user_id', $request['user_id'])
                ->where('huawei_token', $request['huawei_token'])
                ->where('package_name', $request['package_name'])
                ->first();
        } else {
            $checkDeviceExist = Device::where('user_id', $request['user_id'])
                ->where('fcm_token', $request['fcm_token'])
                ->where('package_name', $request['package_name'])
                ->first();
        }

        if (is_null($checkDeviceExist)) {
            $device = new CreateDeviceAction;
            $device->execute($request);
        } else {
            $device = new UpdateDeviceAction;
            $device->execute($request, $checkDeviceExist);
        }

        return $device;
    }

    public function deviceRemove(RemoveDeviceRequest $request)
    {
        $devices = Device::where('package_name', $request->package_name)->where('device_id', $request->device_id)->get();

        if (count($devices) > 0) {
            foreach ($devices as $device) {
                $device->delete();
            }

            return $device;
        }
        throw new GeneralException(JsonResponse::HTTP_NOT_FOUND, 'No Device Found');
    }
}
