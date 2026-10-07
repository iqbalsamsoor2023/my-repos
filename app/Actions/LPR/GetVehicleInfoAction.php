<?php

namespace App\Actions\LPR;

use App\Http\Integrations\IApp\LPR\Requests\GetCarInfoRequest;
use GuzzleHttp\Psr7\Stream;

class GetVehicleInfoAction
{
    public function execute($request)
    {
        $data = [];

        if (! empty($request->file)) {
            $stream = new Stream(fopen($request->file, 'r'));

            $data[] = [
                'name' => 'file',
                'value' => $stream,
                'filename' => $request->file->getClientOriginalName(),
            ];
        }

        $request = new GetCarInfoRequest($data);
        $response = $request->send();

        return json_decode($response);
    }
}
