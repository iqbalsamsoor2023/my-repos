<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Services\IncidentReportService;
use Exception;
use Illuminate\Http\Request;

class IncidentReportController extends Controller
{
    protected $service;

    public function __construct(IncidentReportService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/incident-reports",
     *     summary="Get Incident Reports",
     *     description="Retrieve incident reports based on MMB unit ID, ID, and MMB residence ID",
     *     tags={"Incident Reports"},
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
     *         name="mmb_unit_id",
     *         in="query",
     *         description="MMB Unit ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="ID of the incident report",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="mmb_residence_id",
     *         in="query",
     *         description="MMB Residence ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=3019)
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
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="test"),
     *                 @OA\Property(property="description", type="string", example="Sksososla"),
     *                 @OA\Property(property="image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2021-10-08 10:18:47"),
     *                 @OA\Property(property="unit", type="object",
     *                     @OA\Property(property="unit_number", type="string", example="102/101"),
     *                 ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/incident-reports?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/incident-reports?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/incident-reports"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=11),
     *             @OA\Property(property="total", type="integer", example=11),
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
     */
    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
