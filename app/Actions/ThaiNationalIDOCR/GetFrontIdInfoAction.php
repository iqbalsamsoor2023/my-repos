<?php

namespace App\Actions\ThaiNationalIDOCR;

use App\Http\Integrations\IApp\ThaiNationalIDOCR\Requests\GetFrontRequest;
use GuzzleHttp\Psr7\Stream;

class GetFrontIdInfoAction
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

        $request = new GetFrontRequest($data);
        $response = $request->send();

        return json_decode($response);
    }
}
