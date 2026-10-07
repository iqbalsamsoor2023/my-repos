<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\GetCommentRequest;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    protected $service;

    public function __construct(SupportTicketService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/support-tickets",
     *     summary="Get Support Tickets",
     *     description="Retrieve support tickets",
     *     tags={"Support Tickets"},
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
     *         name="category",
     *         in="query",
     *         description="Name of the category",
     *         required=false,
     *
     *         @OA\Schema(type="string"),
     *         example="Neighbour Complain"
     *     ),
     *
     *     @OA\Parameter(
     *         name="resident_user_id",
     *         in="query",
     *         description="ID of the resident user",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=29
     *     ),
     *
     *     @OA\Parameter(
     *         name="pm_user_id",
     *         in="query",
     *         description="ID of the property management user",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=21
     *     ),
     *
     *     @OA\Parameter(
     *         name="unit_id",
     *         in="query",
     *         description="ID of the house unit",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=16
     *     ),
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="same user ID in both submitted and assigned fields",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=16
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=96),
     *                 @OA\Property(property="case_generated_no", type="string", example="03019-230814-C9"),
     *                 @OA\Property(property="unit_id", type="integer", example=2),
     *                 @OA\Property(property="unit_number", type="string", example="101"),
     *                 @OA\Property(property="content", type="string", example="js"),
     *                 @OA\Property(property="chat_category_id", type="integer", example=1),
     *                 @OA\Property(property="chat_category", type="string", example="General Questions"),
     *                 @OA\Property(property="chat_category_item_id", type="integer", example=11),
     *                 @OA\Property(property="category", type="string", example="สอบถามการใช้งาน"),
     *                 @OA\Property(property="status", type="string", example="new"),
     *                 @OA\Property(property="submitted_by", type="integer", example=29),
     *                 @OA\Property(property="submitted_name", type="string", example="Amie"),
     *                 @OA\Property(property="assigned_to", type="integer", example=21),
     *                 @OA\Property(property="assigned_name", type="string", example="103005pm03019"),
     *                 @OA\Property(property="read_at", type="string", format="datetime", example="2023-08-14 17:07:37"),
     *                 @OA\Property(property="unread_indicator", type="boolean", example=false),
     *                 @OA\Property(property="attachment_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2023-08-14 17:07:16"),
     *             )),
     *             @OA\Property(property="http_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     * )
     *
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return success($response);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'http_code' => $ex->getCode()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);      
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/v1/support-tickets/{id}",
     *     summary="Show Support Ticket Information",
     *     description="Show information for a specific support ticket based on ID",
     *     tags={"Support Tickets"},
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
     *         description="ID of the support ticket",
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
     *                 @OA\Property(property="id", type="integer", example=7),
     *                 @OA\Property(property="unit_id", type="integer", example=10),
     *                 @OA\Property(property="case_generated_no", type="string", example="00000-230720-C2"),
     *                 @OA\Property(property="chat_category_item_id", type="integer", example=16),
     *                 @OA\Property(property="platform_identifier", type="string", example="mmb"),
     *                 @OA\Property(property="content", type="string", example="house 101 parked at my area"),
     *                 @OA\Property(property="category", type="string", example="Neighbour Complain"),
     *                 @OA\Property(property="submitted_by", type="integer", example=21),
     *                 @OA\Property(property="assigned_to", type="integer", example=384),
     *                 @OA\Property(property="read_at", type="string", format="date-time", example="2023-07-20 23:32:14"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-08-28 12:56:05"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-08-28 12:56:05"),
     *                 @OA\Property(property="attachment_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="unit", type="string", example="107"),
     *                 @OA\Property(property="assigned_to_name", type="string", example="zawanah"),
     *                 @OA\Property(property="submitted_by_name", type="string", example="103005pm03019"),
     *             ),
     *             @OA\Property(property="http_code", type="integer", example=200),
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
     *         description="Not Found - Support ticket not found"
     *     ),
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function show(int $id, Request $request)
    {
        try {
            $response = $this->service->show($id, $request);

            return success($response);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'http_code' => $ex->getCode()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);      
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/support-tickets",
     *     summary="Create Support Ticket",
     *     description="Endpoint to create a new support ticket",
     *     tags={"Support Tickets"},
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
     *         description="Support ticket data",
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *                 required={
     *                     "unit_id",
     *                     "platform_identifier",
     *                     "chat_category_id",
     *                     "chat_category_item_id",
     *                     "content",
     *                     "submitted_by",
     *                     "assigned_to",
     *                     "file"
     *                 },
     *
     *                 @OA\Property(property="unit_id", type="integer", example=12),
     *                 @OA\Property(property="platform_identifier", type="string", example="mmb"),
     *                 @OA\Property(property="chat_category_id", type="integer", example=3),
     *                 @OA\Property(property="chat_category_item_id", type="integer", example=17),
     *                 @OA\Property(property="content", type="string", example="my pet lost since morning"),
     *                 @OA\Property(property="submitted_by", type="integer", example=175),
     *                 @OA\Property(property="assigned_to", type="integer", example=21),
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="The file to be uploaded"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful support ticket creation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="http_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Support Ticket successfully submitted"),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *
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
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        try {
            return $this->service->create($request);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'http_code' => $ex->getStatusCode()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'http_code' => $ex->getCode()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);      
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/support-tickets/{id}",
     *     summary="Update Support Ticket by ID",
     *     description="Update support ticket status",
     *     tags={"Support Tickets"},
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
     *         description="ID of the support ticket",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=242)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="status update",
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="data", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=96),
     *                      @OA\Property(property="unit_id", type="integer", example=2),
     *                      @OA\Property(property="case_generated_no", type="string", example="03019-230814-C9"),
     *                      @OA\Property(property="chat_category_item_id", type="integer", example=null),
     *                      @OA\Property(property="platform_identifier", type="string", example="mmb"),
     *                      @OA\Property(property="content", type="integer", example="js"),
     *                      @OA\Property(property="category", type="string", example="สอบถามการใช้งาน"),
     *                      @OA\Property(property="submitted_by", type="integer", example=21),
     *                      @OA\Property(property="assigned_to", type="integer", example=21),
     *                      @OA\Property(property="read_at", type="string", format="datetime", example="2023-08-14 21:00:14"),
     *                      @OA\Property(property="status", type="string", example="in progress"),
     *                      @OA\Property(property="created_at", type="string", format="datetime", example="2023-08-14 20:48:03"),
     *                      @OA\Property(property="updated_at", type="string", format="datetime", example="2025-09-17 21:25:03"),
     *                      @OA\Property(property="attachment_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                      @OA\Property(property="chat_category_item", type="object", nullable=true),
     *                      @OA\Property(property="comments", type="array", nullable=true, @OA\Items(type="object")),
     *                 )),
     *                 @OA\Property(property="http_code", type="integer", example=200),
     *                 @OA\Property(property="message", type="string", example="Support Ticket successfully updated"),
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             )
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success"
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
     * @param Request $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, int $id)
    {
        try {
            return $this->service->update($request, $id);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'http_code' => $ex->getCode()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);      
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/support-tickets/read-status/{id}",
     *     summary="Update Support Ticket Read Status by ID",
     *     description="Update support ticket status read_at status",
     *     tags={"Support Tickets"},
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
     *         description="ID of the support ticket",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=244)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success update read at."
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
     * @param  int  $id
     * @return Response
     */
    public function read(Request $request, int $id)
    {
        try {
            return $this->service->read($request, $id);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'http_code' => $ex->getCode()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);      
        }
    }

    /**
     * comment the specified resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/support-tickets/comments",
     *     summary="Create Support Ticket Comment",
     *     description="Endpoint to create a new support ticket comment",
     *     tags={"Support Tickets"},
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
     *         description="Support ticket comment. Either content or comment_image is required.",
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="user_id", type="integer", example=29),
     *                 @OA\Property(property="commentable_id", type="integer", example=292),
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     description="Text content (required if no file is provided)",
     *                     example="test"
     *                 ),
     *                 @OA\Property(
     *                     property="comment_image",
     *                     type="string",
     *                     format="binary",
     *                     description="PDF or image file (required if no content is provided, max size: 20MB)"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful send support tciket comment",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="string", example="29"),
     *                 @OA\Property(property="commentable_type", type="string", example="App\Models\SupportTicket"),
     *                 @OA\Property(property="commentable_id", type="string", example="292"),
     *                 @OA\Property(property="content", type="string", example="test"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-07 16:54:13"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-07 16:54:13"),
     *                 @OA\Property(property="id", type="integer", example=413),
     *                 @OA\Property(property="media_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *             ),
     *             @OA\Property(property="http_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Comment successfully send"),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *
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
     * @param Request $request
     * @return Response
     */
    public function comment(Request $request)
    {
        try {
            return $this->service->comment($request);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'http_code' => $ex->getCode()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);      
        }
    }

    /**
     * Display a listing of the resource.
     *
     *  @OA\Get(
     *     path="/api/v1/support-tickets/comments",
     *     summary="Get Comments",
     *     description="Retrieve information about a specific support ticket comments",
     *     operationId="getSupportTicketComment",
     *     tags={"Support Tickets"},
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
     *         name="commentable_type",
     *         in="query",
     *         description="Name of the model",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="string",
     *             example="App\Models\SupportTicket"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="commentable_id",
     *         in="query",
     *         required=true,
     *         description="ID of the commentable entity associated with the comment",
     *
     *         @OA\Schema(type="integer", example=290)
     *     ),
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         required=false,
     *         description="ID of the current logged in user in application",
     *
     *         @OA\Schema(type="integer", example=21)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sender_user_id",
     *         in="query",
     *         required=false,
     *         description="ID of the user that comments at support ticket",
     *
     *         @OA\Schema(type="integer", example=29)
     *     ),
     *
     *     @OA\Parameter(
     *         name="has_pagination",
     *         in="query",
     *         description="Fill in if need data without pagination",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *           @OA\Property(property="data", type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=25),
     *                 @OA\Property(property="user_id", type="integer", example=21),
     *                 @OA\Property(property="commentable_type", type="string", example="App\Models\SupportTicket"),
     *                 @OA\Property(property="commentable_id", type="integer", example=7),
     *                 @OA\Property(property="content", type="string", example="please move your car before 7pm"),
     *                 @OA\Property(property="read_at", type="string", format="datetime", example="2024-10-07 14:06:01"),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2023-07-20 23:34:16"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2023-07-20 23:34:16"),
     *                 @OA\Property(property="media_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="user_name", type="string", example="103005pm03019"),
     *                 @OA\Property(property="user_profile_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="unread_indicator", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="paginatorInfo", type="object",
     *                 @OA\Property(property="count", type="integer", example=20),
     *                 @OA\Property(property="currentPage", type="integer", example=1),
     *                 @OA\Property(property="hasMorePages", type="boolean", example=false),
     *                 @OA\Property(property="total", type="integer", example=5),
     *             ),
     *         ),
     *         @OA\Property(property="http_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string", example="Success"),
     *         @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function showComment(GetCommentRequest $request)
    {
        try {
            $response = $this->service->viewComment($request);

            return success($response);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'http_code' => $ex->getCode()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);      
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/support-tickets/comment/read-status/{id}",
     *     summary="Update Support Ticket Comment Read Status by ID",
     *     description="Update support ticket comment status read_at status",
     *     tags={"Support Tickets"},
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
     *         description="ID of the support ticket comment",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=413)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success update read at."
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
     * @param Request $request
     * @param  int  $id
     * @return Response
     */
    public function readComment(Request $request, int $id)
    {
        try {
            return $this->service->readComment($request, $id);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'http_code' => $ex->getCode()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);      
        }
    }

    /**
     * Get list of resident which have support ticket only
     *
     * @OA\Get(
     *     path="/api/v1/support-tickets/list",
     *     summary="Get Support Ticket Resident Lists",
     *     description="Retrieve resident lists",
     *     tags={"Support Tickets"},
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
     *         description="ID of the resident user",
     *         required=true,
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
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="mmb_id", type="string", example="030191819901000103"),
     *                 @OA\Property(property="unit", type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="unit_number", type="string", example="102/101"),
     *                 ),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=80551),
     *                     @OA\Property(property="name", type="string", example="zawanah C"),
     *                     @OA\Property(property="email", type="string", example="zawanah.test@gmail.com"),
     *                     @OA\Property(property="profile_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 ),
     *                 @OA\Property(property="support_ticket_indicator", type="boolean", example=true),
     *             )
     *          ),
     *          @OA\Property(property="links", type="object",
     *              @OA\Property(property="first", type="string", example="https://dashboard.mymooban.co.th/api/v1/support-tickets/list?page=1"),
     *              @OA\Property(property="last", type="string", example="https://dashboard.mymooban.co.th/api/v1/support-tickets/list?page=3"),
     *              @OA\Property(property="prev", type="string", example=null),
     *              @OA\Property(property="next", type="string", example="https://dashboard.mymooban.co.th/api/v1/support-tickets/list?page=2"),
     *          ),
     *          @OA\Property(property="meta", type="object",
     *              @OA\Property(property="current_page", type="integer", example=1),
     *              @OA\Property(property="from", type="integer", example=1),
     *              @OA\Property(property="last_page", type="integer", example=3),
     *              @OA\Property(property="links", type="array",
     *
     *                   @OA\Items(type="object",
     *
     *                      @OA\Property(property="url", type="string", example="https://test/api/v1/support-tickets/list?page=1"),
     *                      @OA\Property(property="label", type="string", example="1"),
     *                      @OA\Property(property="active", type="boolean", example=false),
     *                   )
     *              ),
     *              @OA\Property(property="path", type="string", example="http://dashboard.mymooban.co.th/api/v1/support-tickets/list"),
     *              @OA\Property(property="per_page", type="integer", example=10),
     *              @OA\Property(property="to", type="integer", example=10),
     *              @OA\Property(property="total", type="integer", example=29),
     *          ),
     *          @OA\Property(property="http_code", type="integer", example=200),
     *          @OA\Property(property="message", type="string", example="Success"),
     *          @OA\Property(property="status", type="boolean", example=true)
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
    public function ticketList(Request $request)
    {
        try {
            $response = $this->service->ticketList($request);

            return $response;
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'http_code' => $ex->getCode()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);      
        }
    }

    /**
     * Get unread count of support ticket for user
     *
     * @OA\Get(
     *     path="/api/v1/support-tickets/unread-count/{user_id}",
     *     summary="Total Unread Support Ticket",
     *     description="Show total unread support tickets",
     *     tags={"Support Tickets"},
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
     *         description="ID of the resident user or property management user",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=29)
     *     ),
     *
     *     @OA\Parameter(
     *         name="unit_id",
     *         in="query",
     *         description="ID of the house unit (to be filled in if the user_id belongs to a resident)",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\Response(
     *        response=200,
     *        description="Successfully retrieved the count of support tickets",
     *
     *        @OA\JsonContent(
     *           type="object",
     *
     *           @OA\Property(property="count_support_ticket", type="integer", example=10),
     *           @OA\Property(property="count_support_ticket_pm", type="integer", example=66)
     *        )
     *     ),
     * )
     *
     * @param Request $request
     * @param  int  $user_id
     * @return Response
     */
    public function unreadCount(Request $request, int $user_id)
    {
        try {
            $response = $this->service->unreadCount($request, $user_id);

            return $response;
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'http_code' => $ex->getCode()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);      
        }
    }

    /**
     * Get unread count of support ticket for PM
     *
     * @param  int  $user_id
     * @return Response
     */
    public function unreadCountByPm(int $user_id)
    {
        try {
            $response = $this->service->unreadCountByPm($user_id);

            return $response;
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'http_code' => $ex->getCode()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);      
        }
    }
}
