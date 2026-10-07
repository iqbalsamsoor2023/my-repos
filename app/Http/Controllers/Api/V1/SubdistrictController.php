<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Repositories\SubdistrictRepository;
use Exception;
use Illuminate\Http\Request;

class SubdistrictController extends Controller
{
    protected $subdistrictRepository;

    public function __construct(SubdistrictRepository $subdistrictRepository)
    {
        $this->subdistrictRepository = $subdistrictRepository;
    }

    /**
     * Display a listing of the resource.
     *
     *  * @OA\Get(
     *     path="/api/v1/subdistricts",
     *     summary="Get Subdistricts",
     *     description="Retrieve information for subdistricts based on district ID",
     *     tags={"Subdistricts"},
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
     *         name="district_id",
     *         in="query",
     *         description="ID of the district",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=749)
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
     *         response="200",
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *           @OA\Property(property="data", type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=5960),
     *                 @OA\Property(property="code", type="integer", example=730101),
     *                 @OA\Property(property="name_in_thai", type="integer", example="พระปฐมเจดีย์"),
     *                 @OA\Property(property="name_in_english", type="string", example="Phra Pathom Chedi"),
     *                 @OA\Property(property="latitude", type="string", example="0.000"),
     *                 @OA\Property(property="longitude", type="string", example="0.000"),
     *                 @OA\Property(property="district_id", type="integer", example=749),
     *                 @OA\Property(property="zip_code", type="integer", example=null),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/subdistricts?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/subdistricts?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/subdistricts"),
     *             @OA\Property(property="per_page", type="integer", example=25),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=25),
     *             @OA\Property(property="total", type="integer", example=25),
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
     *
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            $response = $this->subdistrictRepository->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
