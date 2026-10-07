<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use stdClass;
use Throwable;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AmenityBooking\StoreAmenityBookingRequest;
use App\Http\Resources\AmenityBooking\AmenityBookingCollection;
use App\Http\Resources\AmenityBooking\AmenityBookingResource;
use App\Services\AmenityBookingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AmenityBookingController extends Controller
{
    protected $service;

    public function __construct(AmenityBookingService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/amenity-bookings",
     *     summary="Get Amenity Bookings",
     *     description="Fetch amenity boooking details",
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
     *         name="user_id",
     *         in="query",
     *         description="The ID of the user",
     *
     *         @OA\Schema(type="integer", example=29)
     *     ),
     *
     *     @OA\Parameter(
     *         name="unit_id",
     *         in="query",
     *         description="The ID of the unit",
     *
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\Parameter(
     *         name="ref_no",
     *         in="query",
     *         description="The booking number",
     *
     *         @OA\Schema(type="string", example="KMXCQGA8QVFO")
     *     ),
     *
     *     @OA\Parameter(
     *         name="booking_date",
     *         in="query",
     *         description="The booking date in YYYY-MM-DD",
     *
     *         @OA\Schema(type="string", example="2025-06-23")
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
     *                     @OA\Property(property="booking_number", type="string", example="KMXCQGA8QVFO"),
     *                     @OA\Property(property="amenity", type="object",
     *                          @OA\Property(property="id", type="integer", example=2),
     *                          @OA\Property(property="type", type="string", example="ResidenceAmenity"),
     *                          @OA\Property(property="amenity_name", type="string", example="Yoga Room"),
     *                          @OA\Property(property="amenity_sub_name", type="string", example="null"),
     *                     ),
     *                     @OA\Property(property="status", type="string", example="Approved"),
     *                     @OA\Property(property="booking_date", type="string", example="Monday, June 23, 2025"),
     *                     @OA\Property(property="booking_time", type="string", example="4:00 PM - 6:00 PM"),
     *                     @OA\Property(property="user", type="object",
     *                          @OA\Property(property="id", type="integer", example=29),
     *                          @OA\Property(property="name", type="string", example="Amie"),
     *                     ),
     *                     @OA\Property(property="unit", type="object",
     *                          @OA\Property(property="id", type="integer", example=3),
     *                          @OA\Property(property="unit_number", type="string", example="102/101"),
     *                     ),
     *                     @OA\Property(property="icon_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="qr_code", type="string", example="data:image/png;base64"),
     *                 )),
     *                 @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/amenity-bookings?page=1"),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=1),
     *                 @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/amenity-bookings?page=1"),
     *                 @OA\Property(property="links", type="array", @OA\Items(
     *                     @OA\Property(property="url", type="string", example=null),
     *                     @OA\Property(property="label", type="string", example="Previous"),
     *                     @OA\Property(property="active", type="boolean", example=false),
     *                 )),
     *                 @OA\Property(property="next_page_url", type="string", example="null"),
     *                 @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/amenity-bookings"),
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

            return success(new AmenityBookingCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        }
    }

    public function store(StoreAmenityBookingRequest $request)
    {
        try {
            $response = $this->service->create($request);

            return success($response);
        } catch (Exception $ex) {
            return error($ex->getMessage(), new stdClass, $ex->getCode() ?: JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *      path="/api/v1/amenity-bookings/{identifier}",
     *      summary="Get Amenity Booking by ID or Booking Number",
     *      description="Retrieve details of a specific amenity booking based on its ID or Booking Number.",
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
     *          name="identifier",
     *          description="ID or Booking Number of the Amenity Booking",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(type="string", example="WNAP8ZBBUMZO")
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="Successful response",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="booking_number", type="string", example="WNAP8ZBBUMZO"),
     *                  @OA\Property(property="amenity", type="object",
     *                       @OA\Property(property="id", type="integer", example=2),
     *                       @OA\Property(property="type", type="string", example="ResidenceAmenityOption"),
     *                       @OA\Property(property="amenity_name", type="string", example="Swimming Pool"),
     *                       @OA\Property(property="amenity_sub_name", type="string", example="Kid Pool"),
     *                  ),
     *                  @OA\Property(property="status", type="string", example="Approved"),
     *                  @OA\Property(property="booking_date", type="string", example="Thursday, June 19, 2025"),
     *                  @OA\Property(property="booking_time", type="string", example="10:00 AM - 12:00 PM"),
     *                  @OA\Property(property="user", type="object",
     *                       @OA\Property(property="id", type="integer", example=24),
     *                       @OA\Property(property="name", type="string", example="Kelly Kong"),
     *                  ),
     *                  @OA\Property(property="unit", type="object",
     *                       @OA\Property(property="id", type="integer", example=3),
     *                       @OA\Property(property="unit_number", type="string", example="102/101"),
     *                  ),
     *                  @OA\Property(property="icon_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                  @OA\Property(property="qr_code", type="string", example="data:image/png;base64"),
     *             ),
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
    public function show(string $identifier)
    {
        try {
            $response = is_numeric($identifier)
                ? $this->service->show((int) $identifier)
                : $this->service->findByRefNumber($identifier);

            return success(new AmenityBookingResource($response));
        } catch (ModelNotFoundException $ex) {
            return notFound();
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Throwable $ex) {
            return response()->json(['message' => $ex->getMessage(), 'trace' => $ex->getTrace()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
