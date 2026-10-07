<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicClaimItemCollection;
use App\Services\PublicClaimItemService;
use Illuminate\Http\Request;

class PublicClaimableItemController extends Controller
{
    protected $service;

    public function __construct(PublicClaimItemService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/public-claimable-items",
     *     summary="Get Public Claim Items",
     *     description="Retrieve public claim items based on residence ID and/or claimable item name",
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
     *         name="residence_id",
     *         in="query",
     *         description="ID of the residence",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=3019
     *     ),
     *
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Name of the claimable item",
     *         required=false,
     *
     *         @OA\Schema(type="string"),
     *         example="Test A"
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
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="name", type="string", example="Yoga Room (Room A)"),
     *                 @OA\Property(property="model_type", type="string", example="App\Models\ResidenceAmenityOption"),
     *                 @OA\Property(property="claimable_items", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                          @OA\Property(property="claimable_title_id", type="integer", example=1),
     *                          @OA\Property(property="claimable_title_name", type="string", example="Lighting")
     *                     ),example={
     *                          {"claimable_title_id": 1, "claimable_title_name": "Lighting"},
     *                          {"claimable_title_id": 2, "claimable_title_name": "Electricity"}
     *                     }
     *                 ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/public-claimable-items?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/public-claimable-items?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/public-claimable-items?page=1"),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/public-claimable-items"),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=3),
     *             @OA\Property(property="total", type="integer", example=3),
     *         ),
     *         @OA\Property(property="http_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string", example="Success"),
     *         @OA\Property(property="status", type="string", example="true")
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

            return success(new PublicClaimItemCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
}
