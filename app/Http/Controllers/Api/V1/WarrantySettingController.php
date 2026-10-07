<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\WarrantySetting\GetWarrantySettingAction;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\WarrantySetting\WarrantySettingCollection;
use Exception;
use Illuminate\Http\Request;

class WarrantySettingController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/warranty-settings",
     *     summary="Get Warranty Settings",
     *     description="Retrieve information for warranty setting based on residence ID",
     *     tags={"Warranty Setting"},
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
     *     @OA\Parameter(
     *         name="residence_id",
     *         in="query",
     *         description="ID of the residence",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=3019)
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *           @OA\Property(property="data", type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="warranty_handbook_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="has_warranty_reminder", type="boolean", example=true),
     *                 @OA\Property(property="has_appointment_schedule", type="boolean", example=true),
     *                 @OA\Property(property="has_verification", type="boolean", example=true)
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/other-amenities?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/other-amenities?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/other-amenities"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=1),
     *             @OA\Property(property="total", type="integer", example=1),
     *         ),
     *          @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            $getWarrantySettingAction = new GetWarrantySettingAction;
            $response = $getWarrantySettingAction->execute($request);

            return success(new WarrantySettingCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
