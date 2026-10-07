<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Http\Controllers\Controller;
use App\Services\SupportTicketStatusService;
use Exception;

class SupportTicketStatusController extends Controller
{
    protected $service;

    public function __construct(SupportTicketStatusService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/support-ticket-statuses",
     *     summary="Get Support Ticket Statuses",
     *     description="Retrieve support ticket statuses based on residence ID",
     *     tags={"Support Ticket Statuses"},
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
     *         name="status",
     *         in="query",
     *         description="status of the ticket",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="in progress")
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
     *                      @OA\Property(property="status", type="string", example="New"),
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
     * @return Response
     */
    public function index()
    {
        try {
            $response = $this->service->index();

            return success($response);
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
