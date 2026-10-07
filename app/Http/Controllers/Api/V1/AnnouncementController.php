<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateReadStatusRequest;
use App\Http\Resources\AnnoucementResource;
use App\Services\AnnouncementService;
use Exception;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    protected $service;

    public function __construct(AnnouncementService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/announcements",
     *     summary="Get a list of announcements",
     *     tags={"Announcement"},
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
     *             example="en"
     *         ),
     *         description="The language of the response"
     *     ),
     *
     *     @OA\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="The ID of the role",
     *
     *         @OA\Schema(type="integer"),
     *         example=2
     *     ),
     *
     *     @OA\Parameter(
     *         name="residence_id",
     *         in="query",
     *         description="The ID of the residence",
     *
     *         @OA\Schema(type="integer"),
     *         example=3019
     *     ),
     *
     *     @OA\Parameter(
     *         name="unit_id",
     *         in="query",
     *         description="The ID of the unit",
     *
     *         @OA\Schema(type="integer"),
     *         example=3
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page numbering",
     *
     *         @OA\Schema(type="integer"),
     *         example=2
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=327),
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="title", type="string", example="AGM Meeting"),
     *                 @OA\Property(property="description", type="string", example="First Meeting - cancel"),
     *                 @OA\Property(property="image_urls", type="string", example="[]"),
     *                 @OA\Property(property="document_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_by", type="integer", example=21),
     *                 @OA\Property(property="updated_by", type="integer", example=21),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2021-03-12 21:52:32"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2021-03-12 21:57:11"),
     *                 @OA\Property(property="read_at_indicator", type="boolean", example=true),
     *             )),
     *         @OA\Property(property="http_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string", example="Success"),
     *         @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="400",
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not Found"
     *     )
     * )
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return success(AnnoucementResource::collection($response));
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
     *     path="/api/v1/announcements",
     *     summary="Create a new announcement",
     *     tags={"Announcement"},
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
     *             example="en"
     *         ),
     *         description="The language of the response"
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="title", type="string", example="Sample Title"),
     *                 @OA\Property(property="description", type="string", example="Sample Description"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_by", type="integer", example=21),
     *                 @OA\Property(property="updated_by", type="integer", example=21),
     *                 @OA\Property(property="images", type="array", @OA\Items(type="string", format="binary")),
     *                 @OA\Property(property="attachment", type="string", format="binary"),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="200", description="Success",
     *
     *        @OA\JsonContent(
     *
     *           @OA\Property(property="data", type="object",
     *             @OA\Property(property="id", type="integer", example=16093),
     *             @OA\Property(property="residence_id", type="integer", example=3019),
     *             @OA\Property(property="title", type="string", example="night neon run"),
     *             @OA\Property(property="description", type="string", example="putrajaya 27 sept"),
     *             @OA\Property(property="image_urls", type="string", example="[]"),
     *             @OA\Property(property="document_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *             @OA\Property(property="is_active", type="integer", example=1),
     *             @OA\Property(property="created_by", type="integer", example=21),
     *             @OA\Property(property="updated_by", type="integer", example=21),
     *             @OA\Property(property="created_at", type="string", example="2025-09-23 20:55:58"),
     *             @OA\Property(property="updated_at", type="string", example="2025-09-23 20:55:58"),
     *             @OA\Property(property="read_at_indicator", type="boolean", example=true),
     *           ),
     *           @OA\Property(property="http_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Success"),
     *           @OA\Property(property="status", type="boolean", example=true)
     *       )
     *     ),
     *
     *     @OA\Response(response="400", description="Bad request"),
     *     @OA\Response(response="422", description="Validation error"),
     * )
     *
     * @param StoreAnnouncementRequest $request
     * @return Response
     */
    public function store(StoreAnnouncementRequest $request)
    {
        try {
            $response = $this->service->create($request);

            return success(new AnnoucementResource($response));
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
     *     path="/api/v1/announcements/{id}",
     *     summary="Show Announcement Information",
     *     description="Show information for a specific announcement based on ID",
     *     tags={"Announcement"},
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
     *         description="ID of the announcement",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=252),
     *                 @OA\Property(property="unit_id", type="integer", example=16),
     *                 @OA\Property(property="platform_identifier", type="string", example="mmb"),
     *                 @OA\Property(property="content", type="string", example="1234"),
     *                 @OA\Property(property="category", type="string", example="สอบถามเรื่องอื่นๆ"),
     *                 @OA\Property(property="status", type="string", example="new"),
     *                 @OA\Property(property="submitted_by", type="integer", example=7801),
     *                 @OA\Property(property="submitted_name", type="string", example="g.gun007@hotmail.com"),
     *                 @OA\Property(property="assigned_to", type="integer", example=7801),
     *                 @OA\Property(property="assigned_name", type="string", example="103005pm03019"),
     *                 @OA\Property(property="read_at", type="string", format="date-time", example="2024-09-02 14:50:12"),
     *                 @OA\Property(property="unread_indicator", type="boolean", example=false),
     *                 @OA\Property(property="attachment_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-08-28 12:56:05"),
     *             ),
     *             @OA\Property(property="message", type="string", example="Success"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Annoucement not found"
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

            return success(new AnnoucementResource($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     *  @OA\Put(
     *     path="/announcement/{id}/update-read-status",
     *     summary="Update announcement read at by ID",
     *     tags={"Announcement"},
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
     *             example="en"
     *         ),
     *         description="The language of the response"
     *     ),
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the announcement to update",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="user_id", type="integer", example=80551, description="ID of the user making the request"),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="200", description="Success",
     *
     *        @OA\JsonContent(
     *
     *           @OA\Property(property="data", type="object"),
     *           @OA\Property(property="http_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Success"),
     *           @OA\Property(property="status", type="boolean", example=true),
     *        )
     *     ),
     *
     *     @OA\Response(response="400", description="Bad request"),
     *     @OA\Response(response="404", description="Not Found"),
     * )
     *
     * @param UpdateReadStatusRequest $request
     * @param  int  $id
     * @return Response
     */
    public function updateReadStatus(UpdateReadStatusRequest $request, int $id)
    {
        try {
            $response = $this->service->updateReadStatus($request, $id);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
