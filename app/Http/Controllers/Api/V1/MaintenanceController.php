<?php

namespace App\Http\Controllers\Api\V1;

use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StoreCommentRequest;
use App\Http\Requests\Maintenance\StoreMaintenanceRequest;
use App\Http\Requests\Maintenance\UpdateMaintenanceRequest;
use App\Http\Resources\Maintenance\MaintenanceCollection;
use App\Http\Resources\Maintenance\MaintenanceResource;
use App\Services\MaintenanceService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MaintenanceController extends Controller
{
    protected $service;

    public function __construct(MaintenanceService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     *  @OA\Get(
     *     path="/api/v1/maintenances",
     *     summary="Get Maintenance Records",
     *     description="Endpoint to retrieve maintenance records with optional parameters",
     *     tags={"Maintenances"},
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
     *         name="maintainable_type",
     *         in="query",
     *         description="Type of the maintainable entity",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"App\Models\ResidenceAmenity", "App\Models\ResidenceAmenityOption", "App\Models\Unit"}),
     *         example="App\Models\Unit"
     *     ),
     *
     *     @OA\Parameter(
     *         name="maintainable_id",
     *         in="query",
     *         description="ID of the maintainable entity",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=123
     *     ),
     *
     *     @OA\Parameter(
     *         name="reported_by",
     *         in="query",
     *         description="ID of the user who reported the maintenance",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=29
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
     *         response=200,
     *         description="Successful response"
     *     ),
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

            return success(new MaintenanceCollection($response));
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
     *     path="/api/v1/maintenances",
     *     summary="Create Maintenance Record",
     *     description="Endpoint to create a maintenance record",
     *     tags={"Maintenances"},
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
     *         description="Maintenance record data",
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                  @OA\Property(property="residence_id", type="integer", example=3019),
     *                  @OA\Property(property="maintainable_id", type="integer", example=2),
     *                  @OA\Property(property="maintainable_type", type="string", example="App\Models\ResidenceAmenity"),
     *                  @OA\Property(property="claimable_title_id", type="integer", example=12, description="Required if maintainable_type is App\\Models\\ResidenceAmenity or App\\Models\\ResidenceAmenityOption"),
     *                  @OA\Property(property="miscellaneous", type="string", example="Additional details"),
     *                  @OA\Property(property="issue_description", type="string", example="clogged drain"),
     *                  @OA\Property(property="appointment_datetime", type="string", format="date-time", example="2025-07-25 09:30:00"),
     *                  @OA\Property(property="images", type="array", @OA\Items(type="string", format="binary")),
     *                  @OA\Property(property="reported_by", type="integer", example=13518),
     *                  @OA\Property(property="warranty_checker", type="integer", enum={0, 1}, description="Required if maintainable_type is App\\Models\\Unit. Accepts only 0 or 1.", example=1),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                  @OA\Property(property="residence_id", type="integer", example=3019),
     *                  @OA\Property(property="maintainable_id", type="integer", example=2),
     *                  @OA\Property(property="maintainable_type", type="string", example="App\Models\ResidenceAmenity"),
     *                  @OA\Property(property="claimable_title_id", type="integer", example=12, description="Required if maintainable_type is App\\Models\\ResidenceAmenity or App\\Models\\ResidenceAmenityOption"),
     *                  @OA\Property(property="miscellaneous", type="string", example="Additional details"),
     *                  @OA\Property(property="issue_description", type="string", example="clogged drain"),
     *                  @OA\Property(property="appointment_datetime", type="string", format="date-time", example="2025-07-25 09:30:00"),
     *                  @OA\Property(property="images", type="array", @OA\Items(type="string", format="binary")),
     *                  @OA\Property(property="reported_by", type="integer", example=13518),
     *                  @OA\Property(property="warranty_checker", type="integer", enum={0, 1}, description="Required if maintainable_type is App\\Models\\Unit. Accepts only 0 or 1.", example=1),
     *             )
     *         ),
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
     *         response=422,
     *         description="Unprocessable Entity - Validation error in the request body"
     *     ),
     * )
     *
     * @param StoreMaintenanceRequest $request
     * @return Response
     */
    public function store(StoreMaintenanceRequest $request)
    {
        try {
            $response = $this->service->create($request);

            return success(new MaintenanceResource($response));
        } catch (ModelNotFoundException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     *   @OA\Get(
     *     path="/api/v1/maintenances/{id}",
     *     summary="Get Maintenance Record by ID",
     *     description="Endpoint to retrieve a maintenance record by ID",
     *     tags={"Maintenances"},
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
     *         description="ID of the maintenance record",
     *         required=true,
     *
     *         @OA\Schema(type="integer"),
     *         example = 123
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
     *         description="Not Found - Maintenance record not found"
     *     ),
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function show(int $id)
    {
        try {
            $response = $this->service->show($id);

            return success(new MaintenanceResource($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * verify the specified resource in storage.
     *
     * * @OA\Put(
     *     path="/api/v1/maintenances/{id}",
     *     summary="Update Maintenance Record",
     *     description="Endpoint to update a maintenance record",
     *     tags={"Maintenances"},
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
     *         description="ID of the maintenance record to update",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Maintenance record update data",
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="status", type="integer", example=1, enum={1, 2}, description="Status should be either 1 or 2"),
     *                 @OA\Property(property="progress_description", type="string", example="Making progress", description="Description of the progress"),
     *                 @OA\Property(property="completed_remark", type="string", example="Task completed successfully", description="Remark for completion"),
     *                 @OA\Property(property="is_verified", type="boolean", example=true, description="Verification status, true for verified, false for not verified"),
     *                 @OA\Property(property="verification_description", type="string", example="Verified successfully", description="Description of the verification"),
     *                 @OA\Property(property="rating", type="integer", example=4, minimum=1, maximum=5, description="Rating between 1 and 5"),
     *                 @OA\Property(property="progress_image", type="string", format="binary", description="Image file for progress"),
     *                 @OA\Property(property="verification_image", type="string", format="binary", description="Image file for verification (required if 'is_verified' is true)"),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                  @OA\Property(property="status", type="integer", example=1, enum={1, 2}, description="Status should be either 1 or 2"),
     *                 @OA\Property(property="progress_description", type="string", example="Making progress", description="Description of the progress"),
     *                 @OA\Property(property="completed_remark", type="string", example="Task completed successfully", description="Remark for completion"),
     *                 @OA\Property(property="is_verified", type="boolean", example=true, description="Verification status, true for verified, false for not verified"),
     *                 @OA\Property(property="verification_description", type="string", example="Verified successfully", description="Description of the verification"),
     *                 @OA\Property(property="rating", type="integer", example=4, minimum=1, maximum=5, description="Rating between 1 and 5"),
     *                 @OA\Property(property="progress_image", type="string", format="binary", description="Image file for progress"),
     *                 @OA\Property(property="verification_image", type="string", format="binary", description="Image file for verification (required if 'is_verified' is true)"),
     *             )
     *         ),
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
     *         response=422,
     *         description="Unprocessable Entity - Validation error in the request body"
     *     )
     * )
     *
     * @param UpdateMaintenanceRequest $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateMaintenanceRequest $request, int $id)
    {
        try {
            $response = $this->service->update($request, $id);

            return success(new MaintenanceResource($response));
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * comment the specified resource in storage.
     *
     * * @OA\Post(
     *     path="/api/v1/maintenances/{id}/comment",
     *     summary="Post Maintenance Comment",
     *     description="Endpoint to post a comment for a maintenance record",
     *     tags={"Maintenances"},
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
     *         description="Maintenance comment data",
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="user_id", type="integer", example=123),
     *                 @OA\Property(property="commentable_type", type="string", example="App\Models\Maintenance"),
     *                 @OA\Property(property="commentable_id", type="integer", example=456),
     *                 @OA\Property(property="content", type="string", example="This is a comment on the maintenance record"),
     *                 @OA\Property(property="comment_image", type="string", format="binary", description="Image file for the comment"),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="user_id", type="string", example="123"),
     *                 @OA\Property(property="commentable_type", type="string", example="App\Models\Maintenance"),
     *                 @OA\Property(property="commentable_id", type="string", example="456"),
     *                 @OA\Property(property="content", type="string", example="This is a comment on the maintenance record"),
     *                 @OA\Property(property="comment_image", type="string", format="binary", description="Image file for the comment"),
     *             )
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Comment posted successfully"
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
     * @param StoreCommentRequest $request
     * @param  int  $id
     * @return Response
     */
    public function comment(StoreCommentRequest $request, int $id)
    {
        try {
            $response = $this->service->comment($request, $id);

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
