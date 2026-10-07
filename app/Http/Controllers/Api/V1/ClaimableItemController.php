<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClaimableItemResource;
use App\Repositories\ClaimableItemRepository;
use Exception;
use Illuminate\Http\Request;

class ClaimableItemController extends Controller
{
    protected $claimableItemRepository;

    public function __construct(ClaimableItemRepository $claimableItemRepository)
    {
        $this->claimableItemRepository = $claimableItemRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/claimable-items",
     *     summary="Get Claimable Items",
     *     description="Retrieve claimable items based on residence ID and item name",
     *     tags={"Claimable Items"},
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
     *         name="item_name",
     *         in="query",
     *         description="Name of the claimable item",
     *         required=false,
     *
     *         @OA\Schema(type="string"),
     *         example="swimming pool"
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
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="item", type="string", example="Swimming Pool (สระว่ายน้ำ)"),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2025-10-26 23:14:12"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2025-11-10 15:32:49"),
     *                 @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                 @OA\Property(property="moobaan", type="object",
     *                     @OA\Property(property="id", type="integer", example=3019),
     *                     @OA\Property(property="name", type="string", example="MMB Thailand Demo 01"),
     *                     @OA\Property(property="name_th", type="string", example="มายหมู่บ้าน 01 (MI Garden)"),
     *                 ),
     *             )),
     *         @OA\Property(property="message", type="string", example="Success")
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
            $response = $this->claimableItemRepository->index($request);

            return success(ClaimableItemResource::collection($response));
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
     *     path="/api/v1/claimable-items/{id}",
     *     summary="Show Claimable Item Information",
     *     description="Show information for a specific claimable item based on ID",
     *     tags={"Claimable Items"},
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
     *         description="ID of the claimable item",
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
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="item", type="string", example="Swimming Pool"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-26 23:14:12"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-11-10 15:32:49"),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", example=null),
     *                 @OA\Property(property="moobaan", type="object",
     *                     @OA\Property(property="id", type="integer", example=3019),
     *                     @OA\Property(property="name", type="string", example="MMB Thailand Demo 01"),
     *                     @OA\Property(property="name_th", type="string", example="มายหมู่บ้าน 01 (MI Garden)"),
     *               ),
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
     *         description="Not Found - Claimable item not found"
     *     ),
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function show(int $id)
    {
        try {
            $response = $this->claimableItemRepository->show($id);

            return success(new ClaimableItemResource($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
