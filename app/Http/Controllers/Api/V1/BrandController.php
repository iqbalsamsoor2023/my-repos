<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Brand\BrandCollection;
use App\Services\BrandService;
use Exception;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    protected $service;

    public function __construct(BrandService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/brands",
     *     summary="Get a list of brands",
     *     tags={"Brands"},
     *     security={
     *         {"bearer_token": {}}
     *     },
     *
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="en-US"),
     *         description="The language of the response"
     *     ),
     *
     *     @OA\Parameter(
     *         name="gpl_priority",
     *         in="query",
     *         description="Filter by GPL priority",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="ids",
     *         in="query",
     *         description="Array of brand IDs.",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="array",
     *
     *             @OA\Items(type="integer")
     *         )
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
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="country_id", type="integer", example=84),
     *                 @OA\Property(property="name", type="string", example="Audi"),
     *                 @OA\Property(property="name_th", type="string", example="อาวดี้"),
     *                 @OA\Property(property="gpl_priority", type="string", example="19"),
     *                 @OA\Property(property="image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/brands?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/brands?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/brands"),
     *             @OA\Property(property="per_page", type="integer", example=25),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=1),
     *             @OA\Property(property="total", type="integer", example=1)
     *           ),
     *           @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return success(new BrandCollection($response));
        } catch (GeneralException $ex) {
            return response(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response(['message' => $ex->getMessage()]);
        }
    }
}
