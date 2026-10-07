<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\LogisticPartnerResource;
use App\Models\LogisticPartner;
use Exception;
use Illuminate\Http\Request;

class LogisticPartnerController extends Controller
{
    protected $logisticPartner;

    public function __construct(LogisticPartner $logisticPartner)
    {
        $this->logisticPartner = $logisticPartner;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/companies-logo",
     *     summary="Get company logos by category",
     *     tags={"Companies Logos"},
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
     *         required=true,
     *         description="Category value to filter logos (enum: courier)",
     *
     *         @OA\Schema(
     *             type="string",
     *             enum={"courier"}
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="category", type="string", example="Courier"),
     *                 @OA\Property(property="modes", type="string", example="Local"),
     *                 @OA\Property(property="name", type="string", example="Thailand Post(EMS)"),
     *                 @OA\Property(property="logo_url", type="string", format="uri", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="03-07-2023 03:35:39 PM"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="03-07-2023 03:35:39 PM"),
     *             )),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="400",
     *         description="Bad request",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            $logisticPartner = $this->logisticPartner->filter($request->all())->get();

            return LogisticPartnerResource::collection($logisticPartner);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
