<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SosManagement\StoreSosManagementRequest;
use App\Http\Requests\SosManagement\UpdateSosManagementRequest;
use App\Http\Resources\SosManagementResource;
use App\Services\SosManagementService;
use Exception;
use Illuminate\Http\Request;

class SosManagementController extends Controller
{
    protected $service;

    public function __construct(SosManagementService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/sos-managements",
     *     summary="Get SOS managements",
     *     tags={"SOS Managements"},
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
     *         name="created_by_id",
     *         in="query",
     *         description="ID of the user who created the SOS management",
     *         required=false,
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Parameter(
     *         name="residence_id",
     *         in="query",
     *         description="ID of the residence associated with the SOS management",
     *         required=false,
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Parameter(
     *         name="accepted_by_ids",
     *         in="query",
     *         description="Array of IDs of users who accepted the SOS management",
     *         required=false,
     *
     *         @OA\Schema(type="array", @OA\Items(type="integer", format="int64"))
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
     *                 @OA\Property(property="id", type="integer", example=547),
     *                 @OA\Property(property="created_by_id", type="integer", example=29),
     *                 @OA\Property(property="unit_id", type="integer", example=3),
     *                 @OA\Property(property="accepted_by_id", type="integer", example=null),
     *                 @OA\Property(property="user_action_request", type="string", example="Call Ambulance"),
     *                 @OA\Property(property="longitude", type="string", example="100.5018"),
     *                 @OA\Property(property="latitude", type="string", example="13.7563"),
     *                 @OA\Property(property="status", type="string", example="Pending"),
     *                 @OA\Property(property="remark", type="integer", example=null),
     *                 @OA\Property(property="created_at", type="string", example="2024-07-19T08:54:10.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2024-07-19T08:54:10.000000Z"),
     *                 @OA\Property(property="deleted_at", type="string", example=null),
     *                 @OA\Property(property="unit", type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="residence_id", type="integer", example=3019),
     *                     @OA\Property(property="residence", type="object",
     *                         @OA\Property(property="media", type="array",
     *
     *                             @OA\Items(
     *                             )
     *                         ),
     *                     ),
     *
     *                     @OA\Property(property="media", type="array",
     *
     *                          @OA\Items(
     *                          )
     *                     ),
     *                 ),
     *
     *                 @OA\Property(property="created_by", type="object",
     *                     @OA\Property(property="id", type="integer", example=29),
     *                     @OA\Property(property="country_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Amie"),
     *                 ),
     *                 @OA\Property(property="accepted_by", type="object",
     *                 ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/sos-managements?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=12),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/sos-managements?page=12"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/sos-managements?page=2"),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/sos-managements"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=25),
     *             @OA\Property(property="total", type="integer", example=237),
     *         ),
     *          @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
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

            return success(SosManagementResource::collection($response));
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
     *     path="/api/v1/sos-managements",
     *     summary="Create SOS management",
     *     tags={"SOS Managements"},
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
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="created_by_name", type="string"),
     *                 @OA\Property(property="unit_no", type="integer"),
     *                 @OA\Property(property="street", type="string"),
     *                 @OA\Property(property="floor", type="string"),
     *                 @OA\Property(property="block", type="string"),
     *                 @OA\Property(property="sos_type", type="string"),
     *                 @OA\Property(property="company_id", type="integer"),
     *             ),
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="created_by_name", type="string"),
     *                 @OA\Property(property="unit_no", type="string"),
     *                 @OA\Property(property="street", type="string"),
     *                 @OA\Property(property="floor", type="string"),
     *                 @OA\Property(property="block", type="string"),
     *                 @OA\Property(property="sos_type", type="string"),
     *                 @OA\Property(property="company_id", type="integer")
     *             ),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="SOS management created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="SOS management created successfully"),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     * )
     *
     * @param StoreSosManagementRequest $request
     * @return Response
     */
    public function store(StoreSosManagementRequest $request)
    {
        try {
            $response = $this->service->create($request);

            return success(new SosManagementResource($response));
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
     *     path="/api/v1/sos-managements/{id}",
     *     summary="Update SOS management",
     *     tags={"SOS Managements"},
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
     *         description="ID of the SOS management to update",
     *         required=true,
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="accepted_by_id", type="integer", nullable=true),
     *                 @OA\Property(property="user_action_request", type="integer", nullable=true, enum={1, 2}),
     *                 @OA\Property(property="status", type="integer", nullable=true, enum={1, 2, 3, 4}),
     *                 @OA\Property(property="remark", type="string", nullable=true),
     *             ),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="SOS management updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="SOS management updated successfully"),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="SOS management not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     * )
     *
     * @param UpdateSosManagementRequest $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateSosManagementRequest $request, int $id)
    {
        try {
            $response = $this->service->update($request, $id);

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
