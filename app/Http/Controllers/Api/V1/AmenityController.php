<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\AmenityResource;
use App\Repositories\AmenityRepository;
use Exception;
use Illuminate\Http\Request;

class AmenityController extends Controller
{
    protected $amenityRepository;

    public function __construct(AmenityRepository $amenityRepository)
    {
        $this->amenityRepository = $amenityRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/amenities",
     *     summary="Get Amenities",
     *     description="Retrieve amenities based on residence ID, amenity name, and private amenity name",
     *     tags={"Amenities"},
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
     *         name="amenity_name",
     *         in="query",
     *         description="Name of the amenity",
     *         required=false,
     *
     *         @OA\Schema(type="string"),
     *         example="Test A"
     *     ),
     *
     *     @OA\Parameter(
     *         name="private_amenity_name",
     *         in="query",
     *         description="Name of the private amenity",
     *         required=false,
     *
     *         @OA\Schema(type="string"),
     *         example="test private amenity"
     *
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
     *                 @OA\Property(property="id", type="integer", example=717),
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="amenity_name", type="string", example="Test A"),
     *                 @OA\Property(property="warranty_period", type="integer", example=1),
     *                 @OA\Property(property="period_type", type="string", example="year"),
     *                 @OA\Property(property="supplier", type="string", example="Developer"),
     *                 @OA\Property(property="is_out_warranty", type="integer", example=0),
     *                 @OA\Property(property="remark", type="string", example="pay first"),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2024-01-19T07:10:03.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2024-05-13T05:01:10.000000Z"),
     *                 @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                 @OA\Property(property="has_warranty", type="integer", example=1),
     *                 @OA\Property(property="residence", type="object",
     *                     @OA\Property(property="id", type="integer", example=3019),
     *                     @OA\Property(property="name", type="string", example="MI Garden"),
     *                     @OA\Property(property="name_th", type="string", example="สวน mi"),
     *                     @OA\Property(property="mooban_type", type="string", example="Public"),
     *                     @OA\Property(property="completion_year", type="string", example="2021"),
     *                     @OA\Property(property="latitude", type="number", format="double", example=13.7263),
     *                     @OA\Property(property="longitude", type="number", format="double", example=100.5102),
     *                 ),
     *                 @OA\Property(property="other_amenity", type="object",
     *                     @OA\Property(property="id", type="integer", example=412),
     *                     @OA\Property(property="residence_id", type="integer", example=3019),
     *                     @OA\Property(property="is_other_amenity", type="integer", example=0),
     *                     @OA\Property(property="remark", type="string", example=null),
     *                     @OA\Property(property="is_show_warranty_reminder", type="integer", example=1),
     *                     @OA\Property(property="remind_day", type="integer", example=10),
     *                     @OA\Property(property="created_at", type="string", format="datetime", example="2024-03-01T06:28:58.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime", example="2024-05-13T07:21:59.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                     @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
     *                     @OA\Property(property="warranty_handbook_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="media", type="string", example="[]"),
     *                 ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/amenities?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=2),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/amenities?page=2"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/amenities?page=2"),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/amenities"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=20),
     *             @OA\Property(property="total", type="integer", example=22),
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
            $response = $this->amenityRepository->index($request);

            $otherAmenity = $response[0]->otherAmenity;

            $is_active = false;

            if (is_null($otherAmenity) == false) {
                $is_active = $otherAmenity->is_other_amenity == 1 ? true : false;
            }

            return success([
                'data' => AmenityResource::collection($response),
                'is_other_amenity' => [
                    'is_active' => $is_active,
                    'remark' => empty($otherAmenity->remark) == false ? $otherAmenity->remark : __('maintenance.service_may_not_be_available'),
                ],
                'appointment_datetime_status' => $response[0]->residence->appointment_datetime_status == 1 ? true : false,
            ]);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
