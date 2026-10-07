<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Repositories\DistrictRepository;
use Exception;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    protected $districtRepository;

    public function __construct(DistrictRepository $districtRepository)
    {
        $this->districtRepository = $districtRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/districts",
     *     summary="Get District Information",
     *     description="Retrieve information about districts based on province ID",
     *     tags={"Districts"},
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
     *         name="province_id",
     *         in="query",
     *         description="ID of the province",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=30)
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
     *                 @OA\Property(property="id", type="integer", example=389),
     *                 @OA\Property(property="code", type="integer", example=4101),
     *                 @OA\Property(property="name_in_thai", type="string", example="เมืองอุดรธานี"),
     *                 @OA\Property(property="name_in_english", type="string", example="Mueang Udon Thani"),
     *                 @OA\Property(property="province_id", type="integer", example=30),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/districts?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/districts?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/districts"),
     *             @OA\Property(property="per_page", type="integer", example=25),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=20),
     *             @OA\Property(property="total", type="integer", example=20),
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
            $response = $this->districtRepository->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
