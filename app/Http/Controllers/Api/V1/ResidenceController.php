<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Residence\ResidenceResource;
use App\Services\ResidenceService;
use Exception;
use Illuminate\Http\Request;

class ResidenceController extends Controller
{
    protected $service;

    public function __construct(ResidenceService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/residences",
     *     summary="Get Residences",
     *     description="Retrieve information for Residences",
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
     *         name="subdistrict_id",
     *         in="query",
     *         description="ID of the SubDistrict",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=7393)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sgoc_residence_guard_user_id",
     *         in="query",
     *         description="ID of the residence guard user",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=7558)
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
     *          response="200",
     *          description="Success",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="current_page", type="integer", example=1),
     *                  @OA\Property(property="data", type="array", @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="id", type="integer", example=3019),
     *                      @OA\Property(property="name", type="string", example="MI Garden"),
     *                      @OA\Property(property="name_th", type="string", example="สวน mi"),
     *                      @OA\Property(property="mooban_type", type="string", example="Public"),
     *                      @OA\Property(property="sub_type", type="integer", example=null),
     *                      @OA\Property(property="completion_year", type="string", example="2021"),
     *                      @OA\Property(property="latitude", type="double", example=13.7263),
     *                      @OA\Property(property="longitude", type="double", example=100.5102),
     *                      @OA\Property(property="subdistrict_id", type="integer", example=7393),
     *                      @OA\Property(property="developer_id", type="integer", example=293),
     *                      @OA\Property(property="developer_user_id", type="integer", example=22),
     *                      @OA\Property(property="property_management_type", type="integer", example=1),
     *                      @OA\Property(property="property_management_id", type="integer", example=441),
     *                      @OA\Property(property="property_management_user_id", type="integer", example=21),
     *                      @OA\Property(property="receptionist_user_id", type="integer", example=23),
     *                      @OA\Property(property="accountant_user_id", type="integer", example=73),
     *                      @OA\Property(property="sgoc_company_id", type="integer", example=258),
     *                      @OA\Property(property="sgoc_residence_guard_user_id", type="integer", example=7558),
     *                      @OA\Property(property="insurance_company_id", type="integer", example=345),
     *                      @OA\Property(property="company_id", type="integer", example=null),
     *                      @OA\Property(property="sales_management_user_id", type="integer", example=null),
     *                      @OA\Property(property="rtm_user_id", type="integer", example=null),
     *                      @OA\Property(property="report_format", type="integer", example=1),
     *                      @OA\Property(property="is_active", type="integer", example=1),
     *                      @OA\Property(property="is_demo", type="integer", example=0),
     *                      @OA\Property(property="residence_activation_status_id", type="integer", example=5),
     *                      @OA\Property(property="internet_provider_id", type="array", @OA\Items(type="string", format="binary", example="2")),
     *                      @OA\Property(property="guard_house_entry_number", type="integer", example=2),
     *                      @OA\Property(property="guard_house_lane_type", type="integer", example=2),
     *                      @OA\Property(property="subscription_start_date", type="string", example="2021-01-01"),
     *                      @OA\Property(property="subscription_end_date", type="string", example="2022-12-31"),
     *                      @OA\Property(property="support_ticket_status", type="integer", example=1),
     *                      @OA\Property(property="appointment_datetime_status", type="integer", example=1),
     *                      @OA\Property(property="created_at", type="string", format="date-time", example="2021-01-27T08:41:37.000000Z"),
     *                      @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-19T04:14:40.000000Z"),
     *                      @OA\Property(property="deleted_at", type="string", example=null),
     *                      @OA\Property(property="image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                      @OA\Property(property="receptionist", type="object",
     *                          @OA\Property(property="id", type="integer", example=23),
     *                          @OA\Property(property="country_id", type="integer", example=1),
     *                          @OA\Property(property="name", type="string", example="103005rc03019"),
     *                          @OA\Property(property="email", type="string", example="103005rc03019@mymooban.co.th"),
     *                          @OA\Property(property="email_verified_at", type="string", format="date-time", example=null),
     *                          @OA\Property(property="pdpa_agreed_at", type="string", format="date-time", example=null),
     *                          @OA\Property(property="id_number", type="string", example=null),
     *                          @OA\Property(property="two_factor_confirmed_at", type="string", example=null),
     *                          @OA\Property(property="phone_no", type="string", example="60120010101"),
     *                          @OA\Property(property="address", type="string", example=null),
     *                          @OA\Property(property="is_community_head_verified", type="string", format="date-time", example="2023-07-03 00:00:00"),
     *                          @OA\Property(property="date_of_birth", type="string", example=null),
     *                          @OA\Property(property="gender", type="integer", example=1),
     *                          @OA\Property(property="passport_number", type="string", example=null),
     *                          @OA\Property(property="passport_expiry", type="string", example=null),
     *                          @OA\Property(property="current_team_id", type="integer", example=null),
     *                          @OA\Property(property="profile_photo_path", type="string", example=null),
     *                          @OA\Property(property="created_at", type="string", format="date-time", example="2022-01-19T04:05:05.000000Z"),
     *                          @OA\Property(property="updated_at", type="string", format="date-time", example="2024-02-26T09:04:21.000000Z"),
     *                          @OA\Property(property="deleted_at", type="string", example=null),
     *                          @OA\Property(property="lang", type="string", example="en"),
     *                          @OA\Property(property="profile_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                          @OA\Property(property="image_base64", type="string", example=null),
     *                          @OA\Property(property="media", type="array", @OA\Items(type="object")),
     *                      ),
     *                      @OA\Property(property="subdistrict", type="object",
     *                          @OA\Property(property="id", type="integer", example=7393),
     *                          @OA\Property(property="code", type="integer", example=103005),
     *                          @OA\Property(property="name_in_thai", type="string", example="จตุจักร"),
     *                          @OA\Property(property="name_in_english", type="string", example="Chatuchak"),
     *                          @OA\Property(property="latitude", type="string", example="0.000"),
     *                          @OA\Property(property="longitude", type="string", example="0.000"),
     *                          @OA\Property(property="district_id", type="integer", example=30),
     *                          @OA\Property(property="zip_code", type="string", example=null),
     *                          @OA\Property(property="district", type="object",
     *                              @OA\Property(property="id", type="integer", example=30),
     *                              @OA\Property(property="code", type="integer", example=1030),
     *                              @OA\Property(property="name_in_thai", type="string", example="เขต จตุจักร"),
     *                              @OA\Property(property="name_in_english", type="string", example="Chatuchak"),
     *                              @OA\Property(property="province_id", type="integer", example=1),
     *                              @OA\Property(property="province", type="object",
     *                                  @OA\Property(property="id", type="integer", example=1),
     *                                  @OA\Property(property="code", type="integer", example=10),
     *                                  @OA\Property(property="name_in_thai", type="string", example="กรุงเทพมหานคร"),
     *                                  @OA\Property(property="name_in_english", type="string", example="Bangkok"),
     *                              ),
     *                          ),
     *                      ),
     *                      @OA\Property(property="facilities", type="array", @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="residence_id", type="integer", example=3019),
     *                          @OA\Property(property="name", type="string", example="KTV Room"),
     *                          @OA\Property(property="booking_per_hour", type="integer", example=20),
     *                          @OA\Property(property="price_per_hour", type="integer", example=15),
     *                          @OA\Property(property="price_per_day", type="integer", example=60),
     *                          @OA\Property(property="is_active", type="integer", example=1),
     *                          @OA\Property(property="created_at", type="string", format="date-time", example="2021-04-07T06:39:10.000000Z"),
     *                          @OA\Property(property="updated_at", type="string", format="date-time", example="2021-04-07T06:39:10.000000Z"),
     *                          @OA\Property(property="deleted_at", type="string", example=null),
     *                      )),
     *                      @OA\Property(property="media", type="array", @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=3044),
     *                          @OA\Property(property="model_type", type="string", example="App\Models\Residence"),
     *                          @OA\Property(property="model_id", type="integer", example=3019),
     *                          @OA\Property(property="uuid", type="string", example="01486b77-cd81-4689-a4f9-a13dfde31a0c"),
     *                          @OA\Property(property="collection_name", type="string", example="cover_image"),
     *                          @OA\Property(property="name", type="string", example="52861622693180"),
     *                          @OA\Property(property="file_name", type="string", example="52861622693180.png"),
     *                          @OA\Property(property="mime_type", type="string", example="image/jpeg"),
     *                          @OA\Property(property="disk", type="string", example="cos"),
     *                          @OA\Property(property="conversions_disk", type="string", example="cos"),
     *                          @OA\Property(property="size", type="integer", example=15984),
     *                          @OA\Property(property="manipulations", type="array", @OA\Items(type="string", format="string", example=null)),
     *                          @OA\Property(property="custom_properties", type="array", @OA\Items(type="string", format="string", example=null)),
     *                          @OA\Property(property="generated_conversions", type="array", @OA\Items(type="string", format="string", example=null)),
     *                          @OA\Property(property="responsive_images", type="array", @OA\Items(type="string", format="string", example=null)),
     *                          @OA\Property(property="order_column", type="integer", example=1),
     *                          @OA\Property(property="created_at", type="string", format="date-time", example="2023-07-03T08:38:12.000000Z"),
     *                          @OA\Property(property="updated_at", type="string", format="date-time", example="2023-07-03T08:38:12.000000Z"),
     *                          @OA\Property(property="original_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                          @OA\Property(property="preview_url", type="string", example=null),
     *                      )),
     *                  )),
     *                  @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/residences?page=1"),
     *                  @OA\Property(property="from", type="integer", example=1),
     *                  @OA\Property(property="last_page", type="integer", example=1),
     *                  @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/residences?page=1"),
     *                  @OA\Property(property="links", type="array", @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="url", type="string", example=null),
     *                      @OA\Property(property="label", type="string", example="Previous"),
     *                      @OA\Property(property="active", type="boolean", example=false),
     *                  )),
     *                  @OA\Property(property="next_page_url", type="string", example=null),
     *                  @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/residences"),
     *                  @OA\Property(property="per_page", type="integer", example=25),
     *                  @OA\Property(property="prev_page_url", type="string", example=null),
     *                  @OA\Property(property="to", type="integer", example=1),
     *                  @OA\Property(property="total", type="integer", example=1),
     *              ),
     *              @OA\Property(property="message", type="string", example="Success"),
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
    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/v1/residences/{id}",
     *     summary="Show residence details",
     *     description="Get details of a specific residence by ID.",
     *     operationId="showResidence",
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
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the residence",
     *
     *         @OA\Schema(type="integer", example=3019)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *          @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="created successfully"),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *     ),
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function show(int $id)
    {
        try {
            $response = $this->service->show($id);

            return success(new ResidenceResource($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
