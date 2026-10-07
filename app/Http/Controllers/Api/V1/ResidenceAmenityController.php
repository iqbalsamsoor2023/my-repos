<?php

namespace App\Http\Controllers\Api\V1;

use Throwable;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ResidenceAmenityCollection;
use App\Http\Resources\ResidenceAmenityResource;
use App\Services\ResidenceAmenityService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ResidenceAmenityController extends Controller
{
    protected $service;

    public function __construct(ResidenceAmenityService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/residence-amenities",
     *     summary="Get Residence Amenities",
     *     description="Fetch amenities details available at the residence",
     *     tags={"Amenity Bookings"},
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
     *         description="The ID of the residence",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="type", type="string", example="residence-amenity"),
     *                     @OA\Property(property="amenity_bookable_type", type="string", example="App\Models\ResidenceAmenity"),
     *                     @OA\Property(property="residence_id", type="integer", example=3019),
     *                     @OA\Property(property="amenity_id", type="integer", example=3),
     *                     @OA\Property(property="amenity_name", type="string", example="Meeting Room"),
     *                     @OA\Property(property="amenity_sub_name", type="string", example="null"),
     *                     @OA\Property(property="icon_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="is_active", type="integer", example=1),
     *                     @OA\Property(property="booking_per_hour", type="integer", example=2),
     *                     @OA\Property(property="price_per_hour", type="string", example="2.00"),
     *                     @OA\Property(property="price_per_day", type="string", example="20.00"),
     *                     @OA\Property(property="timeslots", type="array",
     *
     *                          @OA\Items(type="object",
     *
     *                              @OA\Property(property="id", type="integer"),
     *                              @OA\Property(property="residence_amenity_id", type="integer"),
     *                              @OA\Property(property="quota", type="integer"),
     *                              @OA\Property(property="day", type="integer"),
     *                              @OA\Property(property="start_at", type="string"),
     *                              @OA\Property(property="end_at", type="string"),
     *                              @OA\Property(property="is_active", type="integer")
     *                          ),
     *                          example={
     *                              {
     *                                  "id": 5,
     *                                  "residence_amenity_id": 2,
     *                                  "quota": 3,
     *                                  "day": 1,
     *                                  "start_at": "10:00:00",
     *                                  "end_at": "22:00:00",
     *                                  "is_active": 1
     *                              },
     *                              {
     *                                  "id": 6,
     *                                  "residence_amenity_id": 2,
     *                                  "quota": 5,
     *                                  "day": 3,
     *                                  "start_at": "08:00:00",
     *                                  "end_at": "20:00:00",
     *                                  "is_active": 1
     *                              }
     *                          }
     *                     ),
     *                 )),
     *                 @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/residence-amenities?page=1"),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=1),
     *                 @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/residence-amenities?page=1"),
     *                 @OA\Property(property="links", type="array", @OA\Items(
     *                     @OA\Property(property="url", type="string", example=null),
     *                     @OA\Property(property="label", type="string", example="Previous"),
     *                     @OA\Property(property="active", type="boolean", example=false),
     *                 )),
     *                 @OA\Property(property="next_page_url", type="string", example="null"),
     *                 @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/residence-amenities"),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="prev_page_url", type="string", example="null"),
     *                 @OA\Property(property="to", type="integer", example=2),
     *                 @OA\Property(property="total", type="integer", example=2),
     *             ),
     *             @OA\Property(property="http_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="status", type="string", example="true")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     * )
     */
    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return success(new ResidenceAmenityCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *      path="/api/v1/residence-amenities/{modelType}/{id}",
     *      summary="Get Residence Amenity by ID",
     *      description="Retrieve details of a specific residence amenity based on its ID.",
     *     tags={"Amenity Bookings"},
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
     *          description="ID of the Residence Amenity",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(type="integer")
     *      ),
     *
     *      @OA\Parameter(
     *          name="modelType",
     *          in="path",
     *          description="The type of model being requested. This can be 'residence-amenity', or ' residence-amenity-option'.",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="residence-amenity-option")
     *      ),
     *
     *      @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="The date for the query, use format 'YYYY-MM-DD'",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="2025-06-21")
     *     ),
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="The ID of the user",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=24)
     *     ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="Successful response",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=2),
     *                  @OA\Property(property="type", type="string", example="App\Models\ResidenceAmenityOption"),
     *                  @OA\Property(property="amenity_name", type="string", example="Kid Pool"),
     *                  @OA\Property(property="residence_id", type="integer", example=3019),
     *                  @OA\Property(property="price_per_hour", type="string", example="2.00"),
     *                  @OA\Property(property="price_per_day", type="string", example="15.00"),
     *                  @OA\Property(property="timeslots", type="array",
     *
     *                      @OA\Items(type="object",
     *
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="quota", type="integer"),
     *                          @OA\Property(property="day", type="integer"),
     *                          @OA\Property(property="start_at", type="string"),
     *                          @OA\Property(property="end_at", type="string"),
     *                      ),
     *                      example={
     *                          {
     *                              "id": 4,
     *                              "quota": 5,
     *                              "day": 3,
     *                              "start_at": "10:00:00",
     *                              "end_at": "20:00:00"
     *                          },
     *                          {
     *                              "id": 5,
     *                              "quota": 1,
     *                              "day": 4,
     *                              "start_at": "10:00:00",
     *                              "end_at": "20:00:00"
     *                           }
     *                      }
     *                  ),
     *                  @OA\Property(property="timeslot_intervals", type="array",
     *
     *                      @OA\Items(type="object",
     *
     *                          @OA\Property(property="label", type="string"),
     *                          @OA\Property(property="start_at", type="string"),
     *                          @OA\Property(property="end_at", type="string"),
     *                          @OA\Property(property="status", type="string"),
     *                          @OA\Property(property="color", type="string"),
     *                      ),
     *                      example={
     *                          {
     *                              "label": "10:00 - 11:00",
     *                              "start_at": "10:00",
     *                              "end_at": "11:00",
     *                              "status": "5 slot remaining",
     *                              "color": "blue"
     *                          },
     *                          {
     *                              "label": "11:00 - 12:00",
     *                              "start_at": "11:00",
     *                              "end_at": "12:00",
     *                              "status": "Full",
     *                              "color": "red"
     *                           },
     *                           {
     *                              "label": "19:00 - 20:00",
     *                              "start_at": "19:00",
     *                              "end_at": "20:00",
     *                              "status": "Already booked",
     *                              "color": "green"
     *                           }
     *                      }
     *                  ),
     *              ),
     *             @OA\Property(property="http_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="status", type="string", example="true")
     *          )
     *      ),
     *
     *      @OA\Response(
     *         response="401",
     *         description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Data not found"
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Invalid Model Type"
     *      ),
     * )
     */
    public function show(string $modelType, int $id, Request $request)
    {
        try {
            $date = $request->query('date');
            $userId = $request->query('user_id');

            $response = $this->service->show($modelType, $id, $date, $userId);

            return success(new ResidenceAmenityResource($response));
        } catch (ModelNotFoundException $ex) {
            return notFound();
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Throwable $ex) {
            return response()->json(['message' => $ex->getMessage(), 'trace' => $ex->getTrace()], 500);
        }
    }
}
