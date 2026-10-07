<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Repositories\ParkingRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ParkingController extends Controller
{
    protected $parkingRepository;

    public function __construct(ParkingRepository $parkingRepository)
    {
        $this->parkingRepository = $parkingRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/parkings",
     *     summary="Get Parkings",
     *     description="Endpoint to retrieve parkings information",
     *     tags={"Parkings"},
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
     *         @OA\Schema(type="integer"),
     *         example=3019
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
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="type", type="integer", example=2),
     *                 @OA\Property(property="rate_mode", type="integer", example=1),
     *                 @OA\Property(property="is_discount_coupon", type="integer", example=1),
     *                 @OA\Property(property="discount_type", type="integer", example=2),     *
     *                 @OA\Property(property="created_at", type="string", example="2022-09-01T01:25:35.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2024-06-18T16:36:42.000000Z"),
     *                 @OA\Property(property="deleted_at", type="string", example=null),
     *                 @OA\Property(property="residence", type="object",
     *                     @OA\Property(property="id", type="integer", example=3019),
     *                     @OA\Property(property="name", type="string", example="Mi Garden"),
     *                     @OA\Property(property="media", type="array",
     *
     *                          @OA\Items(
     *                          )
     *                     ),
     *                 ),
     *             )),
     *
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/parkings?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/parkings?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/parkings"),
     *             @OA\Property(property="per_page", type="integer", example=25),
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
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            $response = $this->parkingRepository->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
