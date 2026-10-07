<?php

use Illuminate\Http\Response;

/**
 * Return an all OK 200 response.
 *
 * @param  mixed  $data
 * @return Response
 */
function success($data = null, $message = null, $status = 200)
{
    if (is_null($message)) {
        $message = __('api-response.success.default');
    }

    $response = [
        'data' => $data,
        'http_code' => $status,
        'message' => __($message),
        'status' => true,
    ];

    return response($response, 200);
}

function notFound($data = null, $message = 'Data not found', $message_th = 'ไม่พบข้อมูล')
{
    $response = [
        'data' => is_null($data) ? new stdClass() : $data,
        'http_code' => 404,
        'message' => $message,
        'message_th' => $message_th,
        'status' => false,
    ];

    return response($response, 404);
}

function error($message = null, $data = new stdClass, $status = 500)
{
    if (is_null($message)) {
        $message = __('api-response.error.default');
    }

    return response()->json([
        'data' => $data,
        'status' => false,
        'http_code' => $status,
        'message' => __($message),
    ], $status);
}
