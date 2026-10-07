<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Services\VisitorRemarkService;
use Exception;
use Illuminate\Http\Request;

class VisitorRemarkController extends Controller
{
    protected $service;

    public function __construct(VisitorRemarkService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/visitor-remarks",
     *     summary="Get Visitor Remarks",
     *     description="Retrieve visitor remarks based on residence ID",
     *     tags={"Visitor Remarks"},
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
     *         description="ID of the residence",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=3019)
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful response",
     *
     *          @OA\JsonContent(type="object",
     *
     *              @OA\Property(property="data", type="array",
     *
     *                  @OA\Items(type="object",
     *
     *                      @OA\Property(property="id", type="integer", example=10),
     *                      @OA\Property(property="residence_id", type="integer", example=3019),
     *                      @OA\Property(property="remark", type="string", example="มีตราประทับจอดฟรี 3 ชั่วโมง\nบัตรหายปรับ 500 บาท"),
     *                      @OA\Property(property="created_at", type="string", format="date-time", example="2023-09-21T15:32:45.000000"),
     *                      @OA\Property(property="updated_at", type="string", format="date-time", example="2023-09-21T15:32:45.000000Z"),
     *                  )
     *              ),
     *          @OA\Property(property="message", type="string", example="Success")
     *         ),
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
