<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Invoice\ItemResource;
use App\Services\ItemService;
use Exception;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    protected $service;

    public function __construct(ItemService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/items",
     *     summary="Get Items",
     *     description="Retrieve items based on invoice ID and status",
     *     tags={"Items"},
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
     *         name="invoice_id",
     *         in="query",
     *         description="ID of the invoice",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Status of the item (1-Unpaid, 2-Paid, 3-Partially Paid, 4-Pending, 5-Failed, 6-Cancel)",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="id", type="integer", example=23374),
     *                  @OA\Property(property="invoice_id", type="integer", example=22534),
     *                  @OA\Property(property="home_id", type="string", example="1030050301996433"),
     *                  @OA\Property(property="name", type="string", example="การไฟฟ้า"),
     *                  @OA\Property(property="quantity", type="integer", example=1),
     *                  @OA\Property(property="price", type="number", format="double", example=10.00),
     *                  @OA\Property(property="vat", type="number", format="double", example=0.00),
     *                  @OA\Property(property="status", type="integer", example=4),
     *             )),
     *             @OA\Property(property="http_code", type="number", example=200),
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
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return success(ItemResource::collection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
