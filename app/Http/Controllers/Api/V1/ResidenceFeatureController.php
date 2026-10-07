<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Repositories\ResidenceFeatureRepository;
use Exception;
use Illuminate\Http\Request;

class ResidenceFeatureController extends Controller
{
    protected $residenceFeatureRepository;

    public function __construct(ResidenceFeatureRepository $residenceFeatureRepository)
    {
        $this->residenceFeatureRepository = $residenceFeatureRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/residence-features",
     *     summary="Get Residence Features",
     *     description="Retrieve information for Residence Features",
     *     tags={"Residences"},
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
     *         description="ID of the Residence",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=3019)
     *     ),
     *
     *     @OA\Parameter(
     *         name="feature_id",
     *         in="query",
     *         description="ID of the Features",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=8)
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
            $response = $this->residenceFeatureRepository->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
