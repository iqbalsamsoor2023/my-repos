<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Repositories\VehicleModelRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VehicleModelController extends Controller
{
    protected $vehicleModelRepository;

    public function __construct(VehicleModelRepository $vehicleModelRepository)
    {
        $this->vehicleModelRepository = $vehicleModelRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/vehicle-models",
     *     summary="Get a list of vehicle models",
     *     tags={"Vehicle Models"},
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
     *         name="type",
     *         in="query",
     *         description="Filter by vehicle type",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         description="Filter by vehicle brand",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="has_pagination",
     *         in="query",
     *         description="Fill in if need data without pagination",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *
     *     @OA\Response(
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
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="brand_id", type="integer", example=1),
     *                  @OA\Property(property="name", type="string", example="A4"),
     *                  @OA\Property(property="type", type="integer", example=1),
     *                  @OA\Property(property="created_at", type="string", format="date-time", example="2019-03-06T17:53:06.000000Z"),
     *                  @OA\Property(property="updated_at", type="string", format="date-time", example="2019-03-06T17:53:06.000000Z"),
     *                  @OA\Property(property="deleted_at", type="string", format="date-time", example=null),
     *              )
     *          ),
     *          @OA\Property(property="message", type="string", example="Success")
     *          ),
     *      ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     * )
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        try {
            $response = $this->vehicleModelRepository->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
