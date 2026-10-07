<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Repositories\VisitorSettingRepository;
use Exception;
use Illuminate\Http\Request;

class VisitorSettingController extends Controller
{
    protected $visitorSettingRepository;

    public function __construct(VisitorSettingRepository $visitorSettingRepository)
    {
        $this->visitorSettingRepository = $visitorSettingRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/visitor-settings",
     *     summary="Get Visitor Settings",
     *     description="Retrieve visitor settings based on residence ID",
     *     tags={"Visitor Settings"},
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
     *         @OA\Schema(type="integer"),
     *         example=3019
     *     ),
     *
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="current_page", type="integer", example=1),
     *                  @OA\Property(property="data", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="residence_id", type="integer", example=3019),
     *                      @OA\Property(property="is_qr_active", type="integer", example=3019),
     *                      @OA\Property(property="created_at", type="string", format="date-time", example="2023-07-03T16:38:16.000000Z"),
     *                      @OA\Property(property="updated_at", type="string", format="date-time", example="2024-02-22T15:55:20.000000Z"),
     *                      @OA\Property(property="deleted_at", type="string", example=null),
     *                      @OA\Property(property="pdpa_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                  )),
     *                  @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/visitor-settings?page=1"),
     *                  @OA\Property(property="from", type="integer", example=1),
     *                  @OA\Property(property="last_page", type="integer", example=1),
     *                  @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/visitor-settings?page=1"),
     *                  @OA\Property(property="links", type="array", @OA\Items(
     *                      @OA\Property(property="url", type="string", example=null),
     *                      @OA\Property(property="label", type="string", example="Previous"),
     *                      @OA\Property(property="active", type="boolean", example=false),
     *                  )),
     *                  @OA\Property(property="next_page_url", type="string", example=null),
     *                  @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/visitor-settings"),
     *                  @OA\Property(property="per_page", type="integer", example=20),
     *                  @OA\Property(property="prev_page_url", type="string", example=null),
     *                  @OA\Property(property="to", type="integer", example=1),
     *                  @OA\Property(property="total", type="integer", example=1),
     *              ),
     *              @OA\Property(property="message", type="string", example="Success")
     *          )
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
            $response = $this->visitorSettingRepository->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
