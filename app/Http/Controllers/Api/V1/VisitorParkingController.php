<?php

namespace App\Http\Controllers\Api\V1;

use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisitorParking\GetVisitorParkingCalculationRequest;
use App\Http\Requests\VisitorParking\StoreVisitorParkingRequest;
use App\Services\VisitorParkingService;
use Exception;
use Illuminate\Http\Response;
use stdClass;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VisitorParkingController extends Controller
{
    protected $service;

    public function __construct(VisitorParkingService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/visitor-parkings",
     *     summary="Create a new visitor parking",
     *     tags={"Visitor Parkings"},
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
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="visitor_log_id", type="integer", example=1576843),
     *                 @OA\Property(property="amount_paid", type="integer", example=500),
     *                 @OA\Property(property="is_penalty", type="boolean", example=false),
     *                 @OA\Property(property="is_stamp", type="boolean", example=true),
     *                 @OA\Property(property="vehicle_type", type="integer", example=1),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="visitor_log_id", type="integer", example=1576843),
     *                 @OA\Property(property="amount_paid", type="integer", example=500),
     *                 @OA\Property(property="is_penalty", type="boolean", example=false),
     *                 @OA\Property(property="is_stamp", type="boolean", example=true),
     *                 @OA\Property(property="vehicle_type", type="integer", example=1),
     *             )
     *         )
     *     ),
     *
     * @OA\Response(
     *     response="200",
     *     description="Success",
     *
     *     @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="visitor_log_id", type="string", example="1576843"),
     *             @OA\Property(property="amount_to_pay", type="integer", example=800),
     *             @OA\Property(property="amount_paid", type="string", example="500"),
     *             @OA\Property(property="is_penalty", type="string", example="0"),
     *             @OA\Property(property="is_stamp", type="string", example="1"),
     *             @OA\Property(property="calculation_id", type="integer", example=1058),
     *             @OA\Property(property="calculation_records", type="object",
     *                 @OA\Property(property="id", type="integer", example=1058),
     *                 @OA\Property(property="parking_id", type="integer", example=1),
     *                 @OA\Property(property="vehicle_type", type="integer", example=1),
     *                 @OA\Property(property="is_stamp", type="integer", example=1),
     *                 @OA\Property(property="free_parking_minutes", type="string", nullable="00:00:00"),
     *                 @OA\Property(property="rate_per_hour", type="integer", nullable=20),
     *                 @OA\Property(property="chartered_duration", type="string", example="00:06:00"),
     *                 @OA\Property(property="chartered_price", type="integer", nullable=800),
     *                 @OA\Property(property="penalty", type="integer", example=300),
     *                 @OA\Property(property="created_at", type="string", example="2023-12-05T12:35:18.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2024-06-06T02:15:32.000000Z"),
     *                 @OA\Property(property="deleted_at", type="string", nullable=null),
     *                 @OA\Property(property="parking", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="residence_id", type="integer", example=3019),
     *                     @OA\Property(property="type", type="integer", example=2),
     *                     @OA\Property(property="rate_mode", type="integer", example=1),
     *                     @OA\Property(property="is_discount_coupon", type="integer", example=1),
     *                     @OA\Property(property="discount_type", type="integer", example=2),
     *                     @OA\Property(property="created_at", type="string", example="2022-08-31T17:25:35.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", example="2024-04-27T03:12:50.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", nullable=null),
     *                 )
     *             ),
     *             @OA\Property(property="updated_at", type="string", example="2024-06-06T02:32:47.000000Z"),
     *             @OA\Property(property="created_at", type="string", example="2024-06-06T02:32:47.000000Z"),
     *             @OA\Property(property="id", type="integer", example=124286),
     *             @OA\Property(property="voucher_url", type="string", example="https://mmb2-1258956757.cos.ap-bangkok.myqcloud.com/prod/parking-fees/124286/laravel-icon.png?sign=q-sign-algorithm%3Dsha1%26q-ak%3DAKIDD2cwxgseDWYQ1NwR7QRAn9D6i2kBlOBh%26q-sign-time%3D1717641108%3B1717644768%26q-key-time%3D1717641108%3B1717644768%26q-header-list%3Dhost%26q-url-param-list%3D%26q-signature%3D1c8ef7dbe846a31933728ba33c1515b795a6a630"),
     *             @OA\Property(property="media", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=2420197),
     *                     @OA\Property(property="model_type", type="string", example="App\\Models\\VisitorParking"),
     *                     @OA\Property(property="model_id", type="integer", example =124286),
     *                     @OA\Property(property="uuid", type="string", example = "e026cb11-a349-49a2-a67d-c31a5267b054"),
     *                     @OA\Property(property="collection_name", type="string", example="voucher_image"),
     *                     @OA\Property(property="name", type="string", example="laravel-icon"),
     *                     @OA\Property(property="file_name", type="string", example="laravel-icon.png"),
     *                     @OA\Property(property="mime_type", type="string", example="image/png"),
     *                     @OA\Property(property="disk", type="string", example="cos"),
     *                     @OA\Property(property="conversions_disk", type="string", example="cos"),
     *                     @OA\Property(property="size", type="integer"),
     *                     @OA\Property(property="manipulations", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="custom_properties", type="object",
     *                          @OA\Property(property="type", type="string", example="voucher_image"),
     *                     ),
     *                     @OA\Property(property="generated_conversions", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="responsive_images", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="order_column", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", example="2024-06-06T02:32:48.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", example="2024-06-06T02:32:48.000000Z"),
     *                     @OA\Property(property="original_url", type="string", example="https://mmb2-1258956757.cos.ap-bangkok.myqcloud.com/prod/parking-fees/124286/laravel-icon.png?sign=q-sign-algorithm%3Dsha1%26q-ak%3DAKIDD2cwxgseDWYQ1NwR7QRAn9D6i2kBlOBh%26q-sign-time%3D1717641108%3B1717644768%26q-key-time%3D1717641108%3B1717644768%26q-header-list%3Dhost%26q-url-param-list%3D%26q-signature%3D1c8ef7dbe846a31933728ba33c1515b795a6a630"),
     *                     @OA\Property(property="preview_url", type="string")
     *                 )
     *             )
     *         ),
     *         @OA\Property(property="message", type="string", example="Success")
     *     )
     * ),
     *
     *     @OA\Response(response="400", description="Bad request"),
     *     @OA\Response(response="422", description="Validation error"),
     * )
     */
    public function store(StoreVisitorParkingRequest $request)
    {
        try {
            $response = $this->service->create($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Get visitor parking calculation summary.
     *
     * @OA\Get(
     *     path="/api/v1/visitor-parkings/summary",
     *     summary="Get visitor parking calculation details",
     *     description="Retrieve visitor parking calculation details by visitor log ID",
     *     operationId="getVisitorParkingCalculationDetailbyResidenceId",
     *     tags={"Visitor Parkings"},
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
     *         name="visitor_log_id",
     *         in="query",
     *         description="ID of the visitor log",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1368301)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Visitor not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     * )
     *
     * @param  GetVisitorParkingCalculationRequest  $request
     * @return Response
     */
    public function calculationSummary(GetVisitorParkingCalculationRequest $request)
    {
        try {
            $response = $this->service->calculationSummary($request);

            return success($response);
        } catch (ModelNotFoundException $ex) {
            if ($ex->getModel() == 'App\\Models\\Parking') {
                return error(__('api-response.error.parking_not_found'), new stdClass, Response::HTTP_NOT_FOUND);
            }
            if ($ex->getModel() == 'App\\Models\\ResidenceFeature') {
                return error(__('api-response.error.parking_basic_off'), new stdClass, Response::HTTP_NOT_FOUND);
            }

            return error($ex->getMessage(), new stdClass, Response::HTTP_NOT_FOUND);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}