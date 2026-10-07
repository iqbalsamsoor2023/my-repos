<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Device\RemoveDeviceRequest;
use App\Services\DeviceService;
use Exception;

class DeviceController extends Controller
{
    protected $service;

    public function __construct(DeviceService $service)
    {
        $this->service = $service;
    }

    public function remove(RemoveDeviceRequest $request)
    {
        try {
            $response = $this->service->deviceRemove($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
