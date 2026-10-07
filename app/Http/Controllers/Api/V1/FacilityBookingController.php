<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FacilityBooking\StoreFacilityBookingRequest;
use App\Http\Requests\FacilityBooking\UpdateFacilityBookingRequest;
use App\Http\Resources\Facility\FacilityBookingCollection;
use App\Http\Resources\Facility\FacilityBookingResource;
use App\Interfaces\FacilityBookingRepositoryInterface;
use App\Models\AmenityBooking;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class FacilityBookingController extends Controller
{
    protected $facilityBookingRepository;

    public function __construct(FacilityBookingRepositoryInterface $facilityBookingRepository)
    {
        $this->facilityBookingRepository = $facilityBookingRepository;
    }

    /**
     * Display a listing of the resource.
     *
     *  * @OA\Get(
     *     path="/api/v1/facility-bookings",
     *     summary="Get Facility Bookings",
     *     description="Endpoint to retrieve facility bookings with optional parameters",
     *     tags={"Facility Bookings"},
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
     *         @OA\Schema(type="integer"),
     *         example=1
     *     ),
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="ID of the user",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=29
     *     ),
     *
     *     @OA\Parameter(
     *         name="unit_id",
     *         in="query",
     *         description="ID of the unit",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=3
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
     *                 @OA\Property(property="id", type="integer", example=84),
     *                 @OA\Property(property="facility_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=29),
     *                 @OA\Property(property="unit_id", type="integer", example=3),
     *                 @OA\Property(property="ref_no", type="string", example="C0NZ5QHSIIVQ"),
     *                 @OA\Property(property="start_at", type="string", example="2024-01-24 20:00:00"),
     *                 @OA\Property(property="end_at", type="string", example="2024-01-24 21:00:00"),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(property="created_by", type="integer", example=29),
     *                 @OA\Property(property="updated_by", type="integer", example=null),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2024-01-05T09:49:35.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2024-01-05T09:49:35.000000Z"),
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
     *                     @OA\Property(property="residence", type="object",
     *                     ),
     *                 ),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=29),
     *                     @OA\Property(property="country_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Amie"),
     *                     @OA\Property(property="email", type="string", example="amie.chan@ionnex.com"),
     *                     @OA\Property(property="email_verified_at", type="string", example=null),
     *                     @OA\Property(property="pdpa_agreed_at", type="string", format="datetime", example="2021-01-27 15:56:55"),
     *                     @OA\Property(property="id_number", type="string", example="1234567890123"),
     *                     @OA\Property(property="two_factor_confirmed_at", type="string", example=null),
     *                     @OA\Property(property="phone_no", type="string", example="66812345678"),
     *                     @OA\Property(property="address", type="string", example=null),
     *                     @OA\Property(property="is_community_head_verified", type="string", format="datetime", example="2023-07-03 00:00:00"),
     *                     @OA\Property(property="date_of_birth", type="string", format="datetime", example="2024-04-11 00:00:00"),
     *                     @OA\Property(property="gender", type="integer", example=2),
     *                     @OA\Property(property="passport_number", type="string", example=null),
     *                     @OA\Property(property="passport_expiry", type="string", example=null),
     *                     @OA\Property(property="current_team_id", type="integer", example=null),
     *                     @OA\Property(property="profile_photo_path", type="string", example=null),
     *                     @OA\Property(property="created_at", type="string", format="datetime", example="2021-01-27T08:45:18.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime", example="2024-04-25T02:05:20.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                     @OA\Property(property="lang", type="string", example="en"),
     *                     @OA\Property(property="profile_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="image_base64", type="string", example=null),
     *                     @OA\Property(property="media", type="array", @OA\Items(
     *                        ),
     *                     ),
     *                 ),
     *                 @OA\Property(property="unit", type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="residence_id", type="integer", example=3019),
     *                     @OA\Property(property="invitation_code_owner", type="string", example="1030196257"),
     *                     @OA\Property(property="invitation_code_tenant", type="string", example="2030194233"),
     *                     @OA\Property(property="home_id", type="string", example="1030050301918199"),
     *                     @OA\Property(property="unit_size", type="string", example="1200"),
     *                     @OA\Property(property="myseevr_link", type="string", example="https://vr.realsee.jp/vr/ng0VJ8oMm1NMzlDa/jg6XpEkyjak2Jikh1hpTMlRS6LO0J4Q3/"),
     *                     @OA\Property(property="property_type", type="integer", example=4),
     *                     @OA\Property(property="unit_number", type="string", example="102/101"),
     *                     @OA\Property(property="street", type="string", example="MG1/1A"),
     *                     @OA\Property(property="floor", type="string", example=null),
     *                     @OA\Property(property="block", type="string", example=null),
     *                     @OA\Property(property="status", type="integer", example=1),
     *                     @OA\Property(property="move_in_at", type="string", format="date", example="2023-01-21"),
     *                     @OA\Property(property="created_at", type="string", format="datetime", example="2022-06-10T04:24:34.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime", example="2024-01-19T07:09:50.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                     @OA\Property(property="booking_form_pdf_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="house_contract_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="floor_plan_pdf_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="floor_plan_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="invitation_code_type", type="string", example=null),
     *                     @OA\Property(property="residence", type="object",
     *                     ),
     *                 ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/facility-bookings?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=3),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/facility-bookings?page=3"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/facility-bookings?page=2"),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/facility-bookings"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=20),
     *             @OA\Property(property="total", type="integer", example=51),
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
            $response = $this->facilityBookingRepository->index($request);

            return success(new FacilityBookingCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/facility-bookings",
     *     summary="Create Facility Booking",
     *     description="Endpoint to create a facility booking",
     *     tags={"Facility Bookings"},
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
     *     @OA\RequestBody(
     *         required=true,
     *         description="Facility booking data",
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="facility_id", type="integer", example=16),
     *                 @OA\Property(property="user_id", type="integer", example=123),
     *                 @OA\Property(property="unit_id", type="integer", example=456),
     *                 @OA\Property(property="ref_no", type="string", example="ABC123"),
     *                 @OA\Property(property="start_at", type="string", format="date-time", example="2023-01-01T08:00:00"),
     *                 @OA\Property(property="end_at", type="string", format="date-time", example="2023-01-01T10:00:00"),
     *                 @OA\Property(property="status", type="integer", enum={0, 1, 2, 3, 4}, example=1),
     *                 @OA\Property(property="created_by", type="integer", example=789),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="facility_id", type="integer", example=16),
     *                 @OA\Property(property="user_id", type="integer", example=123),
     *                 @OA\Property(property="unit_id", type="integer", example=456),
     *                 @OA\Property(property="ref_no", type="string", example="ABC123"),
     *                 @OA\Property(property="start_at", type="string", format="date-time", example="2023-01-01T08:00:00"),
     *                 @OA\Property(property="end_at", type="string", format="date-time", example="2023-01-01T10:00:00"),
     *                 @OA\Property(property="status", type="integer", enum={0, 1, 2, 3, 4}, example=1),
     *                 @OA\Property(property="created_by", type="integer", example=789),
     *                 @OA\Property(
     *                     property="bearer_token",
     *                     type="string",
     *                     description="Security token for authentication (Bearer Token)"
     *                 ),
     *             )
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Facility booking created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Facility booking created successfully"),
     *             @OA\Property(property="data", type="object", description="Additional data if needed")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing bearer token"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity - Validation error in the request body"
     *     ),
     * )
     *
     * @param StoreFacilityBookingRequest $request
     * @return Response
     */
    public function store(StoreFacilityBookingRequest $request)
    {
        try {
            $response = $this->facilityBookingRepository->create($request);

            return success(new FacilityBookingResource($response));
        } catch (ModelNotFoundException $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getCode());
        }
    }

    /**
     * Display the specified resource.
     *
     *  * @OA\Get(
     *     path="/api/v1/facility-bookings/{id}",
     *     summary="Get Facility Booking by ID",
     *     description="Endpoint to retrieve a facility booking by ID",
     *     tags={"Facility Bookings"},
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
     *         name="id",
     *         in="path",
     *         description="ID of the facility booking",
     *         required=true,
     *
     *         @OA\Schema(type="integer"),
     *         example=123
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Facility booking not found"
     *     ),
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function show(int $id)
    {
        try {
            $response = AmenityBooking::with('amenityBookable', 'user')->findOrFail($id);

            return success(new FacilityBookingResource($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     *  * @OA\Put(
     *     path="/api/v1/facility-bookings/{id}",
     *     summary="Update Facility Booking by ID",
     *     description="Endpoint to update a facility booking by ID",
     *     tags={"Facility Bookings"},
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
     *         name="id",
     *         in="path",
     *         description="ID of the facility booking",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated facility booking data",
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="status", type="integer", enum={0, 1, 2, 3, 4, 5}),
     *                 @OA\Property(property="updated_by", type="integer"),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="status", type="integer", enum={0, 1, 2, 3, 4, 5}),
     *                 @OA\Property(property="updated_by", type="integer"),
     *             )
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Facility booking not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity - Validation error in the request body"
     *     ),
     * )
     *
     * @param UpdateFacilityBookingRequest $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateFacilityBookingRequest $request, int $id)
    {
        try {
            $response = $this->facilityBookingRepository->update($request, $id);

            return success(new FacilityBookingResource($response));
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
