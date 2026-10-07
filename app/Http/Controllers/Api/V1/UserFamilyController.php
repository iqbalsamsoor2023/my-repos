<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserFamily\ListUserFamilyRequest;
use App\Http\Requests\UserFamily\StoreUserFamilyRequest;
use App\Http\Requests\UserFamily\UpdateUserFamilyRequest;
use App\Http\Resources\UserFamilyResource;
use App\Services\UserFamilyService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

class UserFamilyController extends Controller
{
    protected $service;

    public function __construct(UserFamilyService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user-families",
     *     summary="Get user family",
     *     description="get user family",
     *     operationId="getUserFamily",
     *     tags={"User Families"},
     *     security={{"bearer_token": {}}},
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
     *         name="unit_user_id",
     *         in="query",
     *         required=true,
     *         description="ID of the unit user",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index(ListUserFamilyRequest $request)
    {
        try {
            $response = $this->service->index($request->unit_user_id);

            return success(UserFamilyResource::collection($response));
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/user-families",
     *     summary="Store user family",
     *     description="Store user family",
     *     operationId="storeUserFamily",
     *     tags={"User Families"},
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
     *                 type="object",
     *                 required={
     *                     "name", "email", "password", "phone_no", "date_of_birth", "gender", "relationship",
     *                     "country_id", "unit_id", "role", "is_owner",
     *                 },
     *
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="password", type="string"),
     *                 @OA\Property(property="phone_no", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date"),
     *                 @OA\Property(property="gender", type="string", enum={1, 2}, description="1-male, 2-female"),
     *                 @OA\Property(property="relationship", type="integer", enum={1,2,3,4,5,6,7,8,9,10}, description="1-husband, 2-wife, 3-father, 4-mother, 5-brother, 6-sister, 7-son, 8-daughter, 9-relative, 10-co-home "),
     *                 @OA\Property(property="country_id", type="integer"),
     *                 @OA\Property(property="id_number", type="string"),
     *                 @OA\Property(property="passport_number", type="string"),
     *                 @OA\Property(property="passport_expiry", type="string", format="date"),
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="role", type="string"),
     *                 @OA\Property(property="is_owner", type="boolean"),
     *             ),
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
     *             @OA\Property(property="message", type="string", example="stored successfully"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     ),
     * )
     *
     * @param StoreUserFamilyRequest $request
     * @return Response
     */
    public function store(StoreUserFamilyRequest $request)
    {
        try {
            $response = $this->service->create($request);

            return success(new UserFamilyResource($response));
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/user-families/{id}",
     *     summary="Update user family information",
     *     description="Update user family information for the authenticated user.",
     *     operationId="updateUserFamily",
     *     tags={"User Families"},
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
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="relationship", type="integer", enum={1,2,3,4,5,6,7,8,9,10}, description="1-husband, 2-wife, 3-father, 4-mother, 5-brother, 6-sister, 7-son, 8-daughter, 9-relative, 10-co-home "),
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
     *             @OA\Property(property="http_code", type="number", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     ),
     * )
     *
     * @param UpdateUserFamilyRequest $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateUserFamilyRequest $request, int $id)
    {
        try {
            $response = $this->service->update($request, $id);

            return success(new UserFamilyResource($response));
        } catch (ModelNotFoundException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/v1/user-families/{id}",
     *     summary="Delete user family information",
     *     description="Delete user family information for the authenticated user.",
     *     operationId="deleteUserFamily",
     *     tags={"User Families"},
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
     *             @OA\Property(property="message", type="string", example="User family information deleted successfully"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
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
    public function destroy(int $id)
    {
        try {
            $response = $this->service->delete($id);

            return success($response);
        } catch (ModelNotFoundException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
