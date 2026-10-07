<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parcel\StoreParcelRequest;
use App\Http\Requests\Parcel\UpdateParcelRequest;
use App\Http\Resources\Parcel\ParcelCollection;
use App\Http\Resources\Parcel\ParcelResource;
use App\Models\Parcel;
use App\Services\ParcelService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParcelController extends Controller
{
    protected $service;

    public function __construct(ParcelService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/parcels",
     *     summary="Get Parcels",
     *     description="Endpoint to retrieve parcels information",
     *     tags={"Parcels"},
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
     *         name="qr_code",
     *         in="query",
     *         description="QR code of the parcel",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="string",
     *             example="89lZdBeLbjJ5wqFeOqPrLnIcjesUEd8fXes4m9iJf7XK3QTIdy"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="unit_id",
     *         in="query",
     *         description="ID of the unit associated with the parcel",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="integer",
     *             example=3
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Status of the parcel (0-Pending Pickup, 1-Picked Up, 2-Not my parcel)",
     *         required=false,
     *
     *         @OA\Schema(type="integer", enum={0, 1, 2})
     *     ),
     *
     *     @OA\Parameter(
     *         name="residence_id",
     *         in="query",
     *         description="ID of the residence associated with the parcel",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=3019)
     *     ),
     *
     *     @OA\Parameter(
     *         name="receiver_name",
     *         in="query",
     *         description="Name of the parcel receiver",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="ID of the property management user associated with the parcel",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=21)
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
     *                 @OA\Property(property="id", type="integer", example=273930),
     *                 @OA\Property(property="unit_id", type="integer", example=3),
     *                 @OA\Property(property="receiver_id", type="integer", example=13518),
     *                 @OA\Property(property="receiver_name", type="string", example="zawanah"),
     *                 @OA\Property(property="pickup_person_contact_no", type="string", example=null),
     *                 @OA\Property(property="pickup_person_name", type="string", example=null),
     *                 @OA\Property(property="qr_code", type="string", example="0UhxqLhl6d3cJdPRM6kAaZAUQYd7C3xiPM90yEUbohmO2My6Hv"),
     *                 @OA\Property(property="parcel_generated_no", type="string", example="03019-250604-001"),
     *                 @OA\Property(property="tracking_no", type="string", example="123456"),
     *                 @OA\Property(property="description", type="string", example="test"),
     *                 @OA\Property(property="status", type="integer", example=0),
     *                 @OA\Property(property="pickup_time", type="string", example=null),
     *                 @OA\Property(property="created_by_mmb_user_id", type="integer", example=21),
     *                 @OA\Property(property="created_by_sgoc_user_id", type="integer", example=null),
     *                 @OA\Property(property="image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_urls", type="array", @OA\Items(type="string", example="https://dashboard.mymooban.co.th/images/no-image.png")),
     *                 @OA\Property(property="signature_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="created_at", type="string", example="2025-06-04T13:12:06.000000Z"),
     *                 @OA\Property(property="unit", type="object",
     *                     @OA\Property(property="unit_number", type="string", example="102/101"),
     *                 ),
     *                 @OA\Property(property="courier", type="object",
     *                     @OA\Property(property="name", type="string", example="J&T Express"),
     *                 ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/parcels?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=3),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/parcels?page=3"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/parcels?page=2"),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/parcels"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=20),
     *             @OA\Property(property="total", type="integer", example=46),
     *         ),
     *         @OA\Property(property="http_code", type="number", example=200),
     *         @OA\Property(property="message", type="string", example="Success"),
     *         @OA\Property(property="status", type="boolean", example=true)
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
            $response = $this->service->index($request);

            return success(new ParcelCollection($response));
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
     *     path="/api/v1/parcels",
     *     summary="Create Parcel",
     *     description="Endpoint to create a new parcel",
     *     tags={"Parcels"},
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
     *       required=true,
     *       description="Parcel submission data",
     *
     *       @OA\MediaType(
     *          mediaType="multipart/form-data",
     *
     *          @OA\Schema(
     *             type="object",
     *             required={"role_type", "unit_id", "receiver_name", "images", "created_by"},
     *
     *             @OA\Property(property="role_type", type="integer", enum={1, 2}, example=1, description="1 - PM, 2 - SG"),
     *             @OA\Property(property="unit_id", type="integer", example=123),
     *             @OA\Property(property="receiver_id", type="integer", example=456, nullable=true),
     *             @OA\Property(property="receiver_name", type="string", maxLength=255, example="John Doe"),
     *             @OA\Property(property="courier_id", type="integer", example=789, nullable=true, description="ID of the courier. Required if 'other_courier' is not provided."),
     *             @OA\Property(property="other_courier", type="string", example="GrabExpress", nullable=true, description="Name of the courier if it's not listed. Required if 'courier_id' is not provided."),
     *             @OA\Property(property="tracking_no", type="string", maxLength=50, example="TRACK456", nullable=true),
     *             @OA\Property(property="description", type="string", maxLength=3000, example="Deliver to guardhouse", nullable=true),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *
     *                 @OA\Items(type="string", format="binary")
     *             ),
     *
     *             @OA\Property(property="created_by", type="integer", example=21),
     *          )
     *        )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Parcel created successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity - Validation error in the request body"
     *     ),
     * )
     *
     * @param  StoreParcelRequest  $request
     * @return Response
     */
    public function store(StoreParcelRequest $request)
    {
        try {
            $response = $this->service->create($request);

            return success(new ParcelResource($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/v1/parcels/{id}",
     *     summary="Get Parcel by ID",
     *     description="Retrieve detailed information about a Parcel by its ID",
     *     tags={"Parcels"},
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
     *         description="ID of the Parcel",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=273930),
     *                 @OA\Property(property="unit_id", type="integer", example=3),
     *                 @OA\Property(property="receiver_id", type="integer", example=13518),
     *                 @OA\Property(property="receiver_name", type="string", example="zawanah"),
     *                 @OA\Property(property="pickup_person_contact_no", type="string", example=null),
     *                 @OA\Property(property="pickup_person_name", type="string", example=null),
     *                 @OA\Property(property="qr_code", type="string", example="0UhxqLhl6d3cJdPRM6kAaZAUQYd7C3xiPM90yEUbohmO2My6Hv"),
     *                 @OA\Property(property="parcel_generated_no", type="string", example="03019-250604-001"),
     *                 @OA\Property(property="tracking_no", type="string", example="123456"),
     *                 @OA\Property(property="description", type="string", example="test"),
     *                 @OA\Property(property="status", type="integer", example=0),
     *                 @OA\Property(property="pickup_time", type="string", example=null),
     *                 @OA\Property(property="created_by_mmb_user_id", type="integer", example=21),
     *                 @OA\Property(property="created_by_sgoc_user_id", type="integer", example=null),
     *                 @OA\Property(property="image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_urls", type="array", @OA\Items(type="string", example="https://dashboard.mymooban.co.th/images/no-image.png")),
     *                 @OA\Property(property="signature_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="created_at", type="string", example="2025-06-04T13:12:06.000000Z"),
     *                 @OA\Property(property="unit", type="object",
     *                     @OA\Property(property="unit_number", type="string", example="102/101"),
     *                 ),
     *                 @OA\Property(property="courier", type="object",
     *                     @OA\Property(property="name", type="string", example="J&T Express"),
     *                 ),
     *             ),
     *             @OA\Property(property="http_code", type="number", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Parcel not found"
     *     ),
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function show(int $id)
    {
        try {
            $response = Parcel::with('unit', 'courier')->findOrFail($id);

            return success(new ParcelResource($response));
        } catch (ModelNotFoundException $ex) {
            return response()->json(['message' => 'Parcel not found.'], JsonResponse::HTTP_NOT_FOUND);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/parcels/{id}",
     *     summary="Update Parcel by ID",
     *     description="Update information about a Parcel by its ID",
     *     tags={"Parcels"},
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
     *         description="ID of the Parcel",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Parcel data for update",
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="role_type", type="integer", example=1, enum={1, 2, 3}),
     *                 @OA\Property(property="parcel_id", type="array", @OA\Items(type="integer", example=123)),
     *                 @OA\Property(property="pickup_person_name", type="string", example="John Doe", maxLength=255),
     *                 @OA\Property(property="pickup_person_contact_no", type="string", nullable=true),
     *                 @OA\Property(property="status", type="integer", example=1, enum={0, 1, 2}),
     *                 @OA\Property(property="pickup_type", type="integer", example=1, enum={1, 2, 3}),
     *                 @OA\Property(property="signature_image", type="string", format="binary", description="Image file for signature", example="base64encodedstring"),
     *             )
     *         ),
     *
     *          @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="role_type", type="string", example="1", enum={"1", "2", "3"}),
     *                 @OA\Property(property="parcel_id", type="array", @OA\Items(type="string", example="123")),
     *                 @OA\Property(property="pickup_person_name", type="string", example="John Doe"),
     *                 @OA\Property(property="pickup_person_contact_no", type="string", nullable=true),
     *                 @OA\Property(property="status", type="string", example="1", enum={"0", "1", "2"}),
     *                 @OA\Property(property="pickup_type", type="string", example="1", enum={"1", "2", "3"}),
     *                 @OA\Property(property="signature_image", type="string", format="binary", description="Image file for signature", example="base64encodedstring"),
     *             )
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Parcel updated successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity - Validation error in the request body"
     *     ),
     * )
     *
     * @param  UpdateParcelRequest  $request
     * @return Response
     */
    public function update(UpdateParcelRequest $request)
    {
        try {
            $response = $this->service->update($request);

            return success(new ParcelResource($response));
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/not-my-parcel/{user_id}/update-read-status",
     *     summary="Update Read Status for Not-My-Parcel Notifications",
     *     description="Update the read status for not-my-parcel notifications based on the user ID",
     *     tags={"Parcels"},
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
     *         in="path",
     *         description="ID of the user",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Read status updated successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity - Validation error in the request body"
     *     ),
     * )
     *
     * @param  int  $user_id
     * @return Response
     */
    public function updateUnreadWrongParcelNotificationsForUser(int $user_id)
    {
        try {
            $response = $this->service->updateUnreadWrongParcelNotificationsForUser($user_id);

            return success($response);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
