<?php

namespace App\Http\Controllers\Api\V1;

use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UnitUser\StoreUnitUserRequest;
use App\Http\Resources\UnitUser\UnitUserCollection;
use App\Http\Resources\UnitUser\UnitUserResource;
use App\Models\UnitUser;
use App\Services\UnitUserService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UnitUserController extends Controller
{
    protected $service;

    public function __construct(UnitUserService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/unit-users",
     *     summary="Get unit users",
     *     description="Retrieve unit users based on specified parameters.",
     *     operationId="getUnitUsers",
     *     tags={"Unit Users"},
     *     security={{"bearer_token": {}}},
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
     *         name="is_owner",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="integer", enum={1, 2}),
     *         description="Filter by is_owner (1 or 2)"
     *     ),
     *
     *     @OA\Parameter(
     *         name="unit_id",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         description="Filter by unit_id"
     *     ),
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         description="Filter by user_id"
     *     ),
     *
     *     @OA\Parameter(
     *         name="residence_id",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         description="Filter by residence_id"
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
     *                 @OA\Property(property="id", type="integer", example=2643),
     *                 @OA\Property(property="unit_id", type="integer", example=3),
     *                 @OA\Property(property="user_id", type="integer", example=3708),
     *                 @OA\Property(property="is_owner", type="integer", example=1),
     *                 @OA\Property(property="mmb_id", type="string", example="030191819901040107"),
     *                 @OA\Property(property="is_main_owner", type="integer", example=0),
     *                 @OA\Property(property="is_main_tenant", type="integer", example=0),
     *                 @OA\Property(property="relationship", type="string", example="Brother"),
     *                 @OA\Property(property="approval_status", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2023-11-17T19:46:34.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2023-11-17T19:46:57.000000Z"),
     *                 @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                 @OA\Property(property="unit", type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="residence_id", type="integer", example=3019),
     *                     @OA\Property(property="invitation_code_owner", type="string", example="1030196257"),
     *                     @OA\Property(property="invitation_code_tenant", type="string", example="2030194233"),
     *                     @OA\Property(property="home_id", type="string", example="1030050301918199"),
     *                     @OA\Property(property="unit_size", type="string", example="1200"),
     *                     @OA\Property(property="myseevr_link", type="string", example="https://vr.realsee.jp/vr/ng0VJ8oMm1NMzlDa/jg6XpEkyjak2Jikh1hpTMlRS6LO0J4Q3/"),
     *                     @OA\Property(property="property_type", type="integer", example=4),
     *                     @OA\Property(property="unit_number", type="integer", example="102/101"),
     *                     @OA\Property(property="street", type="integer", example="MG1/1A"),
     *                     @OA\Property(property="floor", type="string", example=null),
     *                     @OA\Property(property="block", type="string", example=null),
     *                     @OA\Property(property="status", type="integer", example=1),
     *                     @OA\Property(property="move_in_at", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="datetime", example="2022-06-10T12:24:34.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime", example="2024-01-19T15:09:50.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                     @OA\Property(property="booking_form_pdf_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="house_contract_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="floor_plan_pdf_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="floor_plan_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="invitation_code_type", type="string", example=null),
     *                     @OA\Property(property="residence", type="object",
     *                         @OA\Property(property="id", type="integer", example=3019),
     *                         @OA\Property(property="name", type="string", example="MI Garden"),
     *                         @OA\Property(property="name_th", type="string", example="สวน mi"),
     *                         @OA\Property(property="mooban_type", type="string", example="Public"),
     *                         @OA\Property(property="completion_year", type="string", example="2021"),
     *                         @OA\Property(property="latitude", type="number", format="double", example=13.7263),
     *                         @OA\Property(property="longitude", type="number", format="double", example=100.5102),
     *                         @OA\Property(property="media", type="array",
     *
     *                            @OA\Items(
     *                            )
     *                         ),
     *
     *                         @OA\Property(property="property_management_user", type="object",
     *                         ),
     *                         @OA\Property(property="residence_guard_user", type="object",
     *                         ),
     *                     ),
     *                     @OA\Property(property="media", type="array",
     *
     *                        @OA\Items(
     *                        )
     *                     ),
     *                 ),
     *
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=29),
     *                     @OA\Property(property="country_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Amie"),
     *                     @OA\Property(property="email", type="string", example="amie.chan@ionnex.com"),
     *                     @OA\Property(property="profile_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="media", type="array",
     *
     *                        @OA\Items(
     *                        )
     *                     ),
     *                 ),
     *             )),
     *
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/unit-users?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/unit-users?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/unit-users"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=3),
     *             @OA\Property(property="total", type="integer", example=3),
     *         ),
     *          @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
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

            return success(new UnitUserCollection($response));
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
     *     path="/api/v1/unit-users",
     *     summary="Create unit users",
     *     description="Create unit users information for a specific unit.",
     *     operationId="createUnitUsers",
     *     tags={"Unit Users"},
     *     security={{"bearer_token": {}}},
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
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="relationship", type="string"),
     *                 @OA\Property(property="is_owner", type="boolean"),
     *                 @OA\Property(property="mmb_id", type="string"),
     *                 @OA\Property(property="approval_status", type="integer", enum={1, 2}),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="relationship", type="string"),
     *                 @OA\Property(property="is_owner", type="boolean"),
     *                 @OA\Property(property="mmb_id", type="string"),
     *                 @OA\Property(property="approval_status", type="integer", enum={1, 2}),
     *             )
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Tenant information created successfully"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object", example={"unit_id": {"The unit_id field is required."}}),
     *         )
     *     ),
     * )
     *
     * @param StoreUnitUserRequest $request
     * @return Response
     */
    public function store(StoreUnitUserRequest $request)
    {
        try {
            $response = $this->service->create($request);

            return success(new UnitUserResource($response));
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
     *     path="/api/v1/unit-users/{id}",
     *     summary="Show unit user",
     *     description="Retrieve information for a specific unit user.",
     *     operationId="showUnitUser",
     *     tags={"Unit Users"},
     *     security={{"bearer_token": {}}},
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
     *
     *         @OA\Schema(type="integer"),
     *         description="ID of the unit user"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Unit user retrieved successfully"),
     *             @OA\Property(property="data", type="object"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     ),
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function show(int $id)
    {
        try {
            $response = UnitUser::with('unit', 'unit.residence', 'unit.residence.subdistrict', 'unit.residence.subdistrict.district', 'unit.residence.subdistrict.district.province', 'user', 'user.roles')->findOrFail($id);

            return success(new UnitUserResource($response));
        } catch (ModelNotFoundException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
