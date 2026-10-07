<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Facility\FacilityTimeslotCollection;
use App\Repositories\FacilityTimeslotRepository;
use Exception;
use Illuminate\Http\Request;

class FacilityTimeslotController extends Controller
{
    protected $facilityTimeslotRepository;

    public function __construct(FacilityTimeslotRepository $facilityTimeslotRepository)
    {
        $this->facilityTimeslotRepository = $facilityTimeslotRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/facility-timeslots",
     *     summary="Get Facility Timeslots",
     *     description="Retrieve facility timeslots based on facility ID, day, and is_active",
     *     tags={"Facility Timeslots"},
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
     *         name="facility_id",
     *         in="query",
     *         description="ID of the facility",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="day",
     *         in="query",
     *         description="Day of the week (1 to 7)",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="integer",
     *             enum={1,2,3,4,5,6,7}
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by is_active status",
     *         required=false,
     *
     *         @OA\Schema(
     *              type="integer",
     *              enum={0,1}
     *         )
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
     *                 @OA\Property(property="facility_id", type="integer", example=1),
     *                 @OA\Property(property="day", type="integer", example=1),
     *                 @OA\Property(property="start_at", type="string", format="date", example="10:00:00"),
     *                 @OA\Property(property="end_at", type="string", format="date", example="21:00:00"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2023-07-03T08:38:39.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2023-07-03T08:38:39.000000Z"),
     *                 @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                 @OA\Property(property="facility", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="residence_id", type="integer", example=3019),
     *                     @OA\Property(property="name", type="string", example="KTV Room"),
     *                     @OA\Property(property="booking_per_hour", type="integer", example=20),
     *                     @OA\Property(property="price_per_hour", type="integer", example=15),
     *                     @OA\Property(property="price_per_day", type="integer", example=60),
     *                     @OA\Property(property="is_active", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="datetime", example="2021-04-07T06:39:10.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime", example="2021-04-07T06:39:10.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                 ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/facility-timeslots?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/facility-timeslots?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/facility-timeslots"),
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
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            $response = $this->facilityTimeslotRepository->index($request);

            return success(new FacilityTimeslotCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
