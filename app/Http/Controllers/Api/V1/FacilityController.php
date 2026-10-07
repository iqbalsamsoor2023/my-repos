<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Facility\FacilityCollection;
use App\Http\Resources\Facility\FacilityResource;
use App\Models\ResidenceAmenity;
use App\Services\FacilityService;
use Exception;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    protected $service;

    public function __construct(FacilityService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     *  @OA\Get(
     *     path="/api/v1/facilities",
     *     summary="Get facilities",
     *     description="Get a list of facilities based on parameters.",
     *     tags={"Facilities"},
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
     *         name="is_active",
     *         in="query",
     *         description="Filter facilities by activity status",
     *         required=false,
     *
     *         @OA\Schema(type="boolean"),
     *         example=true
     *     ),
     *
     *     @OA\Parameter(
     *         name="residence_id",
     *         in="query",
     *         description="Filter facilities by residence ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=3019
     *     ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="Success",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="current_page", type="integer", example=1),
     *                  @OA\Property(property="data", type="array", @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="id", type="integer", example=16),
     *                      @OA\Property(property="residence_id", type="integer", example=3019),
     *                      @OA\Property(property="name", type="string", example="สนามเทนนิส"),
     *                      @OA\Property(property="name_th", type="string", example=""),
     *                      @OA\Property(property="booking_per_hour", type="integer", example=1),
     *                      @OA\Property(property="price_per_hour", type="integer", example=0),
     *                      @OA\Property(property="price_per_day", type="integer", example=0),
     *                      @OA\Property(property="is_active", type="integer", example=1),
     *                      @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-08T16:51:39.000000Z"),
     *                      @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-08T16:52:22.000000Z"),
     *                      @OA\Property(property="deleted_at", type="string", example=null),
     *                      @OA\Property(property="timeslots", type="array", @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=60),
     *                          @OA\Property(property="facility_id", type="integer", example=16),
     *                          @OA\Property(property="day", type="integer", example=0),
     *                          @OA\Property(property="start_at", type="string", format="time", example="08:00:00"),
     *                          @OA\Property(property="end_at", type="string", format="time", example="20:00:00"),
     *                          @OA\Property(property="is_active", type="integer", example=1),
     *                          @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-08T16:51:39.000000Z"),
     *                          @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-08T16:51:39.000000Z"),
     *                          @OA\Property(property="deleted_at", type="string", example=null),
     *                      )),
     *                  )),
     *                  @OA\Property(property="first_page_url", type="string", example="https://dashboard2.mymooban.co.th/api/v1/facilities?page=1"),
     *                  @OA\Property(property="from", type="integer", example=1),
     *                  @OA\Property(property="last_page", type="integer", example=1),
     *                  @OA\Property(property="last_page_url", type="string", example="https://dashboard2.mymooban.co.th/api/v1/facilities?page=1"),
     *                  @OA\Property(property="links", type="array", @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="url", type="string", example=null),
     *                      @OA\Property(property="label", type="string", example="Previous"),
     *                      @OA\Property(property="active", type="boolean", example=false),
     *                  )),
     *                  @OA\Property(property="next_page_url", type="string", example=null),
     *                  @OA\Property(property="path", type="string", example="https://dashboard2.mymooban.co.th/api/v1/facilities"),
     *                  @OA\Property(property="per_page", type="integer", example=20),
     *                  @OA\Property(property="prev_page_url", type="string", example=null),
     *                  @OA\Property(property="to", type="integer", example=16),
     *                  @OA\Property(property="total", type="integer", example=16),
     *              ),
     *              @OA\Property(property="message", type="string", example="Success"),
     *          ),
     *      ),
     *
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized",
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return success(new FacilityCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *      path="/api/v1/facilities/{id}",
     *      summary="Get facility by ID",
     *      description="Retrieve details of a specific facility based on its ID.",
     *      tags={"Facilities"},
     *      security={
     *          {"bearer_token": {}}
     *      },
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
     *      @OA\Parameter(
     *          name="id",
     *          description="ID of the facility",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(type="integer")
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="Successful response",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=16),
     *                  @OA\Property(property="residence_id", type="integer", example=3019),
     *                  @OA\Property(property="name", type="string", example="สนามเทนนิส"),
     *                  @OA\Property(property="name_th", type="string", example=""),
     *                  @OA\Property(property="booking_per_hour", type="integer", example=1),
     *                  @OA\Property(property="price_per_hour", type="integer", example=0),
     *                  @OA\Property(property="price_per_day", type="integer", example=0),
     *                  @OA\Property(property="is_active", type="integer", example=1),
     *                  @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-08T16:51:39.000000Z"),
     *                  @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-08T16:52:22.000000Z"),
     *                  @OA\Property(property="deleted_at", type="string", format="date-time", example=null),
     *                  @OA\Property(property="timeslots", type="array",
     *
     *                      @OA\Items(
     *
     *                          @OA\Property(property="id", type="integer", example=60),
     *                          @OA\Property(property="facility_id", type="integer", example=16),
     *                          @OA\Property(property="day", type="integer", example=0),
     *                          @OA\Property(property="start_at", type="string", format="time", example="08:00:00"),
     *                          @OA\Property(property="end_at", type="string", format="time", example="20:00:00"),
     *                          @OA\Property(property="is_active", type="integer", example=1),
     *                          @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-08T16:51:39.000000Z"),
     *                          @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-08T16:51:39.000000Z"),
     *                          @OA\Property(property="deleted_at", type="string", format="date-time", example=null),
     *                      )
     *                  )
     *              ),
     *              @OA\Property(property="message", type="string", example="Success")
     *          )
     *      ),
     *
     *      @OA\Response(
     *         response="401",
     *         description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Facility not found"
     *      ),
     * )
     * @param  int  $id
     * @return Response
     */
    public function show(int $id)
    {
        try {
            $response = ResidenceAmenity::with('amenityTimeslots')->findOrFail($id);

            return success(new FacilityResource($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
