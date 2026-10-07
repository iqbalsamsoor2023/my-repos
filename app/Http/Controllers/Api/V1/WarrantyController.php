<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Http\Requests\Pet\StorePetRequest;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warranty\CreateWarrantyReminderFeedbackRequest;
use App\Http\Requests\Warranty\WarrantyReminderRequest;
use App\Services\WarrantyService;
use Exception;

class WarrantyController extends Controller
{
    protected $service;

    public function __construct(WarrantyService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource (Out of Warranty Reminder).
     *
     *    @OA\Get(
     *      path="/api/v1/warranty-reminders",
     *      tags={"Warranty Reminders"},
     *      summary="Get warranty reminder",
     *      description="Retrieve warranty reminder based on Residence ID, Unit ID and User ID.",
     *      security={
     *         {"bearer_token": {}}
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
     *          name="residence_id",
     *          in="query",
     *          description="ID of the residence",
     *          required=true,
     *
     *          @OA\Schema(type="integer"),
     *          example=3019
     *      ),
     *
     *      @OA\Parameter(
     *          name="user_id",
     *          in="query",
     *          description="ID of the user",
     *          required=true,
     *
     *          @OA\Schema(type="integer"),
     *          example=13518
     *      ),
     *
     *      @OA\Parameter(
     *          name="unit_id",
     *          in="query",
     *          description="ID of the unit",
     *          required=true,
     *
     *          @OA\Schema(type="integer"),
     *          example=3
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful response",
     *
     *          @OA\JsonContent(
     *          type="object",
     *
     *          @OA\Property(property="data", type="array",
     *
     *              @OA\Items(
     *                  type="object",
     *
     *                  @OA\Property(property="unit_id", type="integer", example=3),
     *                  @OA\Property(property="unit", type="string", example="102/101"),
     *                  @OA\Property(property="amenity_id", type="integer", example=4),
     *                  @OA\Property(property="amenity_name", type="string", example="Others"),
     *                  @OA\Property(property="balance_day", type="integer", example=4),
     *              )
     *          ),
     *          @OA\Property(property="http_code", type="number", example=200),
     *          @OA\Property(property="message", type="string", example="Success"),
     *          @OA\Property(property="status", type="boolean", example=true)
     *          ),
     *      ),
     *
     *      @OA\Response(
     *           response=422,
     *           description="Validation error response",
     *       )
     *      ),
     *
     * @param WarrantyReminderRequest $request
     * @return Response
     */
    public function reminder(WarrantyReminderRequest $request)
    {
        try {
            $response = $this->service->reminder($request);

            return success($response);
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
     *     path="/api/v1/warranty-reminders",
     *     summary="Store feedback from the reminder pop-up",
     *     tags={"Warranty Reminders"},
     *     security={
     *         {"bearerAuth": {}}
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
     *         description="insert related unit, user and amenity data",
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="unit_id", type="integer", example=3),
     *                 @OA\Property(property="user_id", type="integer", example=27),
     *                 @OA\Property(property="amenity_id", type="integer", example=1),
     *                 @OA\Property(property="stop_remind_at", type="string", format="date-time", example="2022-05-11 12:50:41"),
     *                 required={"submitted_by"},
     *             )
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully send stop warranty reminder",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="unit_id", type="string", example="3"),
     *                 @OA\Property(property="user_id", type="string", example="27"),
     *                 @OA\Property(property="amenity_id", type="string", example="1"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-07 16:54:13"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-07 16:54:13"),
     *                 @OA\Property(property="id", type="integer", example=40),
     *             ),
     *             @OA\Property(property="http_code", type="number", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     * )
     *
     * @param StorePetRequest $request
     * @return Response
     */
    public function store(CreateWarrantyReminderFeedbackRequest $request)
    {
        try {
            $response = $this->service->store($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
