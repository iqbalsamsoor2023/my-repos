<?php

namespace App\Http\Controllers\Api\V1;

use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vehicle\StoreVehicleRequest;
use App\Http\Requests\Vehicle\UpdateVehicleRequest;
use App\Http\Resources\Vehicle\VehicleCollection;
use App\Http\Resources\Vehicle\VehicleResource;
use App\Services\VehicleService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VehicleController extends Controller
{
    protected $service;

    public function __construct(VehicleService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/vehicles",
     *     summary="Get Vehicles",
     *     description="Retrieve information about vehicles based on unit ID and user ID",
     *     tags={"Vehicles"},
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
     *         name="unit_id",
     *         in="query",
     *         description="ID of the unit",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="ID of the user",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=29)
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
     *                 @OA\Property(property="id", type="integer", example=50),
     *                 @OA\Property(property="province_id", type="integer", example=1),
     *                 @OA\Property(property="fuel_type", type="integer", example=0),
     *                 @OA\Property(property="plate_number", type="string", example="HRM 8467"),
     *                 @OA\Property(property="policy_no", type="string", example="DQ2291"),
     *                 @OA\Property(property="model_year", type="integer", example=2022),
     *                 @OA\Property(property="insurance_expiry_date", type="string", format="date", example="2023-01-20"),
     *                 @OA\Property(property="roadtax_expiry_date", type="string", format="date", example="2024-01-20"),
     *                 @OA\Property(property="lpr_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="front_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="right_side_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="left_side_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="back_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="insurance_company", type="object",
     *                     @OA\Property(property="name", type="string", example="Bangkok Insurance PCL"),
     *                 ),
     *                 @OA\Property(property="vehicle_model", type="object",
     *                     @OA\Property(property="name", type="string", example="R8"),
     *                     @OA\Property(property="type", type="integer", example=1),
     *                     @OA\Property(property="vehicle_brand", type="object",
     *                        @OA\Property(property="name", type="string", example="Audi"),
     *                     ),
     *                 ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/vehicles?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/vehicles?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/vehicles"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=18),
     *             @OA\Property(property="total", type="integer", example=18),
     *         ),
     *         @OA\Property(property="http_code", type="number", example=200),
     *         @OA\Property(property="message", type="string", example="Success"),
     *         @OA\Property(property="status", type="boolean", example=true)
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

            // db already replace purchased_year with model_year
            $response->getCollection()->transform(function ($vehicle) {
                $vehicle->purchase_year = $vehicle->model_year;

                return $vehicle;
            });

            return success(new VehicleCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/vehicles",
     *     summary="Store vehicle information",
     *     description="Endpoint to store vehicle information.",
     *     tags={"Vehicles"},
     *     security={
     *       {"bearer_token": {}}
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
     *         @OA\JsonContent(
     *             required={"province_id", "unit_id", "user_id", "plate_number", "generated_vehicle_no", "is_access_card", "is_car_sticker"},
     *
     *             @OA\Property(property="province_id", type="integer"),
     *             @OA\Property(property="unit_id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="vehicle_model_id", type="integer", description="Required unless 'model_others' is provided"),
     *             @OA\Property(property="model_others", type="string", description="Optional custom model name if 'vehicle_model_id' is not used"),
     *             @OA\Property(property="brand_id", type="integer", description="Required if 'model_others' is provided. Must exist in vehicle brands."),
     *             @OA\Property(property="vehicle_type", type="integer", enum={1, 2}, description="Required if 'model_others' is provided. 1 = Car, 2 = Motorcycle."),
     *             @OA\Property(property="insurance_company_id", type="integer"),
     *             @OA\Property(property="plate_number", type="string"),
     *             @OA\Property(property="generated_vehicle_no", type="string"),
     *             @OA\Property(property="model_year", type="integer"),
     *             @OA\Property(property="roadtax_expiry_date", type="string", format="date"),
     *             @OA\Property(property="insurance_expiry_date", type="string", format="date"),
     *             @OA\Property(property="policy_no", type="string"),
     *             @OA\Property(property="is_access_card", type="boolean"),
     *             @OA\Property(property="is_car_sticker", type="boolean"),
     *             @OA\Property(property="fuel_type", type="integer"),
     *             @OA\Property(property="image", type="string", format="binary"),
     *             @OA\Property(property="lpr_image", type="string", format="binary"),
     *             @OA\Property(property="front_image", type="string", format="binary"),
     *             @OA\Property(property="back_image", type="string", format="binary"),
     *             @OA\Property(property="right_side_image", type="string", format="binary"),
     *             @OA\Property(property="left_side_image", type="string", format="binary"),
     *             @OA\Property(property="roadtax_image", type="string", format="binary")
     *         ),
     *
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *
     *            @OA\Schema(
     *               type="object",
     *               required={
     *                 "province_id", "unit_id", "user_id", "plate_number", "generated_vehicle_no",
     *                 "is_access_card", "is_car_sticker"
     *               },
     *
     *               @OA\Property(property="province_id", type="integer"),
     *               @OA\Property(property="unit_id", type="integer"),
     *               @OA\Property(property="user_id", type="integer"),
     *               @OA\Property(property="vehicle_model_id", type="integer", description="Required unless 'model_others' is provided."),
     *               @OA\Property(property="model_others", type="string", description="Optional custom model name if 'vehicle_model_id' is not used."),
     *               @OA\Property(property="brand_id", type="integer", description="Required if 'model_others' is provided."),
     *               @OA\Property(
     *                  property="vehicle_type",
     *                  type="integer",
     *                  enum={1, 2},
     *                  description="Required if 'model_others' is provided. 1 = Car, 2 = Motorcycle."
     *               ),
     *               @OA\Property(property="insurance_company_id", type="integer"),
     *               @OA\Property(property="plate_number", type="string"),
     *               @OA\Property(property="generated_vehicle_no", type="string"),
     *               @OA\Property(property="model_year", type="integer"),
     *               @OA\Property(property="roadtax_expiry_date", type="string", format="date"),
     *               @OA\Property(property="insurance_expiry_date", type="string", format="date"),
     *               @OA\Property(property="policy_no", type="string"),
     *               @OA\Property(property="is_access_card", type="boolean"),
     *               @OA\Property(property="is_car_sticker", type="boolean"),
     *               @OA\Property(
     *                  property="fuel_type",
     *                  type="integer",
     *                  enum={1, 2, 3, 4},
     *                  description="Fuel type: 1 = Gasoline, 2 = Diesel, 3 = PHEV (Hybrid), 4 = EV"
     *               ),
     *               @OA\Property(property="image", type="string", format="binary"),
     *               @OA\Property(property="lpr_image", type="string", format="binary"),
     *               @OA\Property(property="front_image", type="string", format="binary"),
     *               @OA\Property(property="back_image", type="string", format="binary"),
     *               @OA\Property(property="right_side_image", type="string", format="binary"),
     *               @OA\Property(property="left_side_image", type="string", format="binary"),
     *               @OA\Property(property="roadtax_image", type="string", format="binary")
     *          )
     *        )
     *     ),
     *
     *     @OA\Response(
     *         response="201",
     *         description="Vehicle information stored successfully"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Validation error"
     *     )
     * )
     *
     * @param StoreVehicleRequest $request
     * @return Response
     */
    public function store(StoreVehicleRequest $request)
    {
        try {
            $response = $this->service->create($request);

            return success(new VehicleResource($response));
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/v1/vehicles/{id}",
     *     summary="Show Vehicle",
     *     description="Retrieve details of a specific vehicle based on ID",
     *     tags={"Vehicles"},
     *     security={{"bearer_token": {}}},
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
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the vehicle",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *    
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="province_id", type="integer", example=1),
     *                 @OA\Property(property="fuel_type", type="integer", example=0),
     *                 @OA\Property(property="plate_number", type="string", example="TES 123"),
     *                 @OA\Property(property="policy_no", type="string", example="TES"),
     *                 @OA\Property(property="model_year", type="integer", example="2021"),
     *                 @OA\Property(property="insurance_expiry_date", type="string", format="date", example="2023-01-20"),
     *                 @OA\Property(property="roadtax_expiry_date", type="string", format="date", example="2024-01-20"),
     *                 @OA\Property(property="lpr_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="front_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="right_side_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="left_side_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="back_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="insurance_company", type="object",
     *                     @OA\Property(property="name", type="string", example="Bangkok Insurance PCL"),
     *                 ),
     *                 @OA\Property(property="vehicle_model", type="object",
     *                     @OA\Property(property="name", type="string", example="R8"),
     *                     @OA\Property(property="type", type="integer", example=1),
     *                     @OA\Property(property="vehicle_brand", type="object",
     *                        @OA\Property(property="name", type="string", example="Audi"),
     *                     ),
     *                 ),
     *             ),
     *             @OA\Property(property="http_code", type="number", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *    
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *    
     *     @OA\Response(
     *         response=404,
     *         description="No query results for model [App\\Models\\Vehicle] 111111"
     *     )
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function show(int $id)
    {
        try {
            $response = $this->service->show($id);

            return success(new VehicleResource($response));
        } catch (ModelNotFoundException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/vehicles/{id}",
     *     summary="Update vehicle information",
     *     description="Endpoint to update vehicle information.",
     *     tags={"Vehicles"},
     *     security={
     *       {"bearer_token": {}}
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
     *         required=true,
     *         description="ID of the vehicle",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="province_id", type="integer"),
     *             @OA\Property(property="unit_id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="vehicle_model_id", type="integer"),
     *             @OA\Property(property="insurance_company_id", type="integer"),
     *             @OA\Property(property="plate_number", type="string"),
     *             @OA\Property(property="generated_vehicle_no", type="string"),
     *             @OA\Property(property="model_year", type="integer"),
     *             @OA\Property(property="roadtax_expiry_date", type="string", format="date"),
     *             @OA\Property(property="insurance_expiry_date", type="string", format="date"),
     *             @OA\Property(property="policy_no", type="string"),
     *             @OA\Property(property="is_access_card", type="boolean"),
     *             @OA\Property(property="is_car_sticker", type="boolean"),
     *             @OA\Property(property="vehicle_image", type="string", format="binary"),
     *             @OA\Property(property="roadtax_image", type="string", format="binary")
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="province_id", type="integer"),
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="vehicle_model_id", type="integer"),
     *                 @OA\Property(property="insurance_company_id", type="integer"),
     *                 @OA\Property(property="plate_number", type="string"),
     *                 @OA\Property(property="generated_vehicle_no", type="string"),
     *                 @OA\Property(property="model_year", type="integer"),
     *                 @OA\Property(property="roadtax_expiry_date", type="string", format="date"),
     *                 @OA\Property(property="insurance_expiry_date", type="string", format="date"),
     *                 @OA\Property(property="policy_no", type="string"),
     *                 @OA\Property(property="is_access_card", type="boolean"),
     *                 @OA\Property(property="is_car_sticker", type="boolean"),
     *                 @OA\Property(property="vehicle_image", type="string", format="binary"),
     *                 @OA\Property(property="roadtax_image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Vehicle information updated successfully"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Vehicle not found"
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Validation error"
     *     )
     * )
     *
     * @param UpdateVehicleRequest $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateVehicleRequest $request, int $id)
    {
        try {
            $response = $this->service->update($request, $id);

            return success($response);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/v1/vehicles/{id}",
     *     summary="Delete vehicle",
     *     description="Endpoint to delete a vehicle.",
     *     tags={"Vehicles"},
     *     security={
     *       {"bearer_token": {}}
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
     *         required=true,
     *         description="ID of the vehicle",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="204",
     *         description="Vehicle deleted successfully"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Vehicle not found"
     *     )
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(int $id)
    {
        try {
            $response = $this->service->delete($id);

            return success($response);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
