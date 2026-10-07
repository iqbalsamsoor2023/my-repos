<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Repositories\CalculationRepository;
use Exception;
use Illuminate\Http\Request;

class CalculationController extends Controller
{
    protected $calculationRepository;

    public function __construct(CalculationRepository $calculationRepository)
    {
        $this->calculationRepository = $calculationRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/calculations",
     *     summary="Get Calculations",
     *     description="Retrieve calculations details",
     *     tags={"Calculations"},
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
     *         name="parking_id",
     *         in="query",
     *         description="ID of the parking",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=1
     *     ),
     *
     *     @OA\Parameter(
     *         name="vehicle_type",
     *         in="query",
     *         description="type of the vehicle",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=1
     *     ),
     *
     *     @OA\Parameter(
     *         name="is_stamp",
     *         in="query",
     *         description="Is Stamp (true/false)",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=0
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
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
            $response = $this->calculationRepository->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
