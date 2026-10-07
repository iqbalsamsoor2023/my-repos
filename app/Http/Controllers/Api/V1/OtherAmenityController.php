<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OtherAmenity\OtherAmenityCollection;
use App\Services\OtherAmenityService;
use Exception;
use Illuminate\Http\Request;

class OtherAmenityController extends Controller
{
    protected $service;

    public function __construct(OtherAmenityService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/other-amenities",
     *     summary="Get Other Amenities",
     *     description="Retrieve information for other amenities based on residence ID",
     *     tags={"Other Amenities"},
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
            $response = $this->service->index($request);

            return success(new OtherAmenityCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
