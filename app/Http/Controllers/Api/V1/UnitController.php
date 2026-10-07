<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetUnitRequest;
use App\Http\Requests\Unit\InvitationCodeValidityRequest;
use App\Http\Resources\UnitResource;
use App\Services\UnitService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

class UnitController extends Controller
{
    protected $service;

    public function __construct(UnitService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/units",
     *     summary="Retrieve units",
     *     description="Get units based on residence_id, unit_number, and name.",
     *     operationId="getUnits",
     *     tags={"Units"},
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
     *         name="residence_id",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="integer", format="int64", example=3019)
     *     ),
     *
     *     @OA\Parameter(
     *         name="unit_number",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="102/101")
     *     ),
     *
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="string")
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
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="unit_number", type="string", example="102/101"),
     *                 @OA\Property(property="street", type="string", example="MG1/1A"),
     *                 @OA\Property(property="floor", type="string", example=null),
     *                 @OA\Property(property="block", type="string", example=null),
     *                 @OA\Property(property="unit_users", type="array",
     *
     *                     @OA\Items(
     *
     *                          @OA\Property(property="unit_id", type="integer", example=3),
     *                          @OA\Property(property="user_id", type="integer", example=13518),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=13518),
     *                              @OA\Property(property="country_id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="zawanah B"),
     *                              @OA\Property(property="email", type="string", example="zawanah123456@gmail.com"),
     *                              @OA\Property(property="email_verified_at", type="datetime", example="2025-09-12 13:15:55"),
     *                              @OA\Property(property="pdpa_agreed_at", type="string", example=null),
     *                              @OA\Property(property="id_number", type="string", example="1234567817265"),
     *                              @OA\Property(property="phone_no", type="string", example="01888888888"),
     *                              @OA\Property(property="address", type="string", example=null),
     *                              @OA\Property(property="is_community_head_verified", type="boolean", example=null),
     *                              @OA\Property(property="date_of_birth", type="date", example="1996-08-08"),
     *                              @OA\Property(property="gender", type="integer", example=2),
     *                              @OA\Property(property="passport_number", type="string", example=null),
     *                              @OA\Property(property="passport_expiry", type="string", example=null),
     *                              @OA\Property(property="created_at", type="datetime", example="2024-06-21 00:37:57"),
     *                              @OA\Property(property="updated_at", type="datetime", example="2024-06-21 00:37:57"),
     *                          ),
     *                     )
     *                 ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/units?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/units?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/units"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=1),
     *             @OA\Property(property="total", type="integer", example=1),
     *         ),
     *          @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     )
     * )
     *
     * @param  GetUnitRequest  $request
     * @return Response
     */
    public function index(GetUnitRequest $request)
    {
        try {
            $response = $this->service->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => $ex->getStatusCode()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => Response::HTTP_INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/v1/units/{id}",
     *     summary="Show unit details",
     *     description="Get details of a specific unit by ID.",
     *     operationId="showUnit",
     *     tags={"Units"},
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
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *          @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="success"),
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

            return success(new UnitResource($response));
        } catch (ModelNotFoundException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => $ex->getStatusCode()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => Response::HTTP_INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Check invitation code validity
     *
     * @OA\Get(
     *     path="/api/v1/units/invitation-code",
     *     summary="Check invitation code validity",
     *     description="Check if the provided invitation code is valid.",
     *     operationId="checkInvitationValidity",
     *     tags={"Units"},
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
     *         name="invitation_code",
     *         in="query",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Valid invitation code"),
     *         )
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
     * @param  InvitationCodeValidityRequest  $request
     * @return Response
     */
    public function invitationCodeValidity(InvitationCodeValidityRequest $request)
    {
        try {
            $response = $this->service->invitationCodeValidity($request);

            return success(new UnitResource($response), 'Valid invitation code');
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => $ex->getStatusCode()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => Response::HTTP_INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
