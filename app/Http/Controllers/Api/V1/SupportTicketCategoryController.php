<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Http\Controllers\Controller;
use App\Services\SupportTicketCategoryService;
use Exception;
use Illuminate\Http\Request;

class SupportTicketCategoryController extends Controller
{
    protected $service;

    public function __construct(SupportTicketCategoryService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/support-ticket-categories",
     *     summary="Get Support Ticket Categories",
     *     description="Retrieve support ticket categories",
     *     tags={"Support Ticket Categories"},
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
     *         name="name",
     *         in="query",
     *         description="name",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="report")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(type="object",
     *
     *              @OA\Property(property="data", type="array",
     *
     *                  @OA\Items(type="object",
     *
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="General Questions"),
     *                  )
     *              ),
     *         @OA\Property(property="http_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string", example="Success"),
     *         @OA\Property(property="status", type="boolean", example=true)
     *         ),
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
            $response = $this->service->index($request);

            return success($response);
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/support-ticket-categories/topics",
     *     summary="Get Support Ticket Category Topics",
     *     description="Retrieve support ticket category topics",
     *     tags={"Support Ticket Categories"},
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
     *         name="chat_category_id",
     *         in="query",
     *         description="chat category id",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(type="object",
     *
     *              @OA\Property(property="data", type="array",
     *
     *                  @OA\Items(type="object",
     *
     *                      @OA\Property(property="id", type="integer", example=17),
     *                      @OA\Property(property="name", type="string", example="Pet Lost"),
     *                  )
     *              ),
     *         @OA\Property(property="http_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string", example="Success"),
     *         @OA\Property(property="status", type="boolean", example=true)
     *         ),
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
    public function indexCategoryTopic(Request $request)
    {
        try {
            $response = $this->service->indexCategoryTopic($request);

            return success($response);
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
