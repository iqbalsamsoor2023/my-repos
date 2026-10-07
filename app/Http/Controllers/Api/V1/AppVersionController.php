<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Exception;

class AppVersionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/app-versions",
     *     summary="Get a list of app versions",
     *     description="Retrieve a list of app versions from the system",
     *     tags={"App Versions"},
     *     security={
     *         {"bearer_token": {}}
     *     },
     *
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="string",
     *             example="en-US"
     *         ),
     *         description="The language of the response"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="A list of applications",
     *
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *
     *                 @OA\Property(property="data", type="array",
     *
     *                     @OA\Items(
     *                         type="object",
     *
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="platform", type="string", example="Android"),
     *                         @OA\Property(property="application_name", type="string", example="MyMooBan"),
     *                         @OA\Property(property="package_identifier", type="string",  example="com.mymooban2.community"),
     *                         @OA\Property(property="build_version", type="string", example="290"),
     *                         @OA\Property(property="application_version", type="string", example="2.1.81"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2022-02-08T07:35:16.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2022-02-08T07:35:16.000000Z"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", example=null),
     *                     ),
     *                 ),
     *             },
     *             @OA\Property(property="message", type="string", example="Success")
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *
     *                 @OA\Property(property="message", type="string", description="Error message"),
     *             },
     *         ),
     *     ),
     * )
     */
    public function index()
    {
        try {
            $query = AppVersion::query();

            if (request()->has('package_identifier')) {
                $query->where('package_identifier', request()->input('package_identifier'));
            }
            if (request()->has('platform')) {
                $query->where('platform', request()->input('platform'));
            }

            $response = $query->get();

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
