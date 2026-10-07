<?php

namespace App\Http\Controllers\Api\V1;

use stdClass;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateProfilePictureRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\UserSearchRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

class UserController extends Controller
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function search(UserSearchRequest $request)
    {
        try {
            $response = $this->service->search($request);

            return success($response);
        } catch (ModelNotFoundException $ex) {
            return error(__('api-response.error.user_not_found'), new stdClass, Response::HTTP_NOT_FOUND);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     *  * @OA\Post(
     *     path="/api/v1/users",
     *     summary="Store user",
     *     description="Store user information.",
     *     operationId="storeUser",
     *     tags={"Users"},
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
     *                 @OA\Property(property="country_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="id_number", type="string"),
     *                 @OA\Property(property="password", type="string"),
     *                 @OA\Property(property="phone_no", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female", "other"}),
     *                 @OA\Property(property="passport_number", type="string"),
     *                 @OA\Property(property="passport_expiry", type="string", format="date"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *                 @OA\Property(property="pdpa_agreed_at", type="string", format="date-time"),
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="relationship", type="string"),
     *                 @OA\Property(property="is_owner", type="boolean"),
     *                 @OA\Property(property="mmb_id", type="string"),
     *                 @OA\Property(property="approval_status", type="string", enum={"pending", "approved", "rejected"}),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="country_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="id_number", type="string"),
     *                 @OA\Property(property="password", type="string"),
     *                 @OA\Property(property="phone_no", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female", "other"}),
     *                 @OA\Property(property="passport_number", type="string"),
     *                 @OA\Property(property="passport_expiry", type="string", format="date"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *                 @OA\Property(property="pdpa_agreed_at", type="string", format="date-time"),
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="relationship", type="string"),
     *                 @OA\Property(property="is_owner", type="boolean"),
     *                 @OA\Property(property="mmb_id", type="string"),
     *                 @OA\Property(property="approval_status", type="string", enum={"pending", "approved", "rejected"}),
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
     * @param StoreUserRequest $request
     * @return Response
     */
    public function store(StoreUserRequest $request)
    {
        try {
            $response = $this->service->create($request);

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
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/v1/users/{id}",
     *     summary="Show user",
     *     description="Retrieve information for a specific user.",
     *     operationId="showUser",
     *     tags={"Users"},
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
     *         @OA\Schema(type="integer", example=21),
     *         description="ID of the user"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=21),
     *                 @OA\Property(property="country_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="103005pm03019"),
     *                 @OA\Property(property="email", type="string", example="103005pm03019@mymooban.co.th"),
     *                 @OA\Property(property="email_verified_at", type="string", example=null),
     *                 @OA\Property(property="pdpa_agreed_at", type="string", example="2567-06-11 10:06:36"),
     *                 @OA\Property(property="id_number", type="string", example="0"),
     *                 @OA\Property(property="two_factor_confirmed_at", type="string", example=null),
     *                 @OA\Property(property="phone_no", type="string", example="06120010101"),
     *                 @OA\Property(property="address", type="string", example=null),
     *                 @OA\Property(property="is_community_head_verified", type="string", example="2023-07-03 00:00:00"),
     *                 @OA\Property(property="created_at", type="string", example="2021-01-27T17:41:37.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2024-07-02T09:01:58.000000Z"),
     *                 @OA\Property(property="deleted_at", type="string", example=null),
     *                 @OA\Property(property="profile_image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_base64", type="string", example="data:image/png;base64,"),
     *                 @OA\Property(property="roles", type="array",
     *
     *                     @OA\Items(
     *
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Property Management"),
     *                         @OA\Property(property="guard_name", type="string", example="web"),
     *                         @OA\Property(property="created_at", type="string", example="2024-02-26T16:10:46.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", example="2024-02-26T16:10:46.000000Z"),
     *                         @OA\Property(property="pivot", type="object",
     *                             @OA\Property(property="model_id", type="integer", example=21),
     *                             @OA\Property(property="role_id", type="integer", example=2),
     *                             @OA\Property(property="model_type", type="string", example="App\Models\User"),
     *                         ),
     *                     ),
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Success"),
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
            $response = User::with([
                'roles',
                'propertyManagement' => function ($q) {
                    $q->with([
                        'subdistrict.district',
                        'developer',
                    ]);
                },
                'receptionist' => function ($q) {
                    $q->with([
                        'subdistrict.district',
                    ]);
                },
                'unitUsers' => function ($q) {
                    $q->with([
                        'unit.residence' => function ($q) {
                            $q->with([
                                'subdistrict.district',
                                'developer',
                            ]);
                        }
                    ]);
                },
            ])->findOrFail($id);

            return success(new UserResource($response));
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
     *     path="/api/v1/users/{id}",
     *     summary="Update user",
     *     description="Update user information for a specific user.",
     *     operationId="updateUser",
     *     tags={"Users"},
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
     *         description="ID of the user"
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
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="country_id", type="integer"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="id_number", type="string"),
     *                 @OA\Property(property="phone_no", type="string"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female", "other"}),
     *                 @OA\Property(property="passport_number", type="string"),
     *                 @OA\Property(property="passport_expiry", type="string", format="date"),
     *                 @OA\Property(property="pdpa_agreed_at", type="string", format="date-time"),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="country_id", type="integer"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="id_number", type="string"),
     *                 @OA\Property(property="phone_no", type="string"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female", "other"}),
     *                 @OA\Property(property="passport_number", type="string"),
     *                 @OA\Property(property="passport_expiry", type="string", format="date"),
     *                 @OA\Property(property="pdpa_agreed_at", type="string", format="date-time"),
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
     *             @OA\Property(property="message", type="string", example="User information updated successfully"),
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
     * @param UpdateUserRequest $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateUserRequest $request, int $id)
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
     * Change the user password
     *
     * @OA\Put(
     *     path="/api/v1/users/{id}/change-password",
     *     summary="Change user password",
     *     description="Change the password for the authenticated user.",
     *     operationId="changeUserPassword",
     *     tags={"Users"},
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
     *         description="ID of the user"
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
     *                 @OA\Property(property="password", type="string"),
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
     *             @OA\Property(property="message", type="string", example="Password changed successfully"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *     ),
     * )
     *
     * @param ChangePasswordRequest $request
     * @param  int  $id
     * @return User
     */
    public function changePassword(ChangePasswordRequest $request, int $id)
    {
        try {
            $response = $this->service->changePassword($request, $id);

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
     * Update the user profile picture
     *
     * @OA\Put(
     *     path="/api/v1/users/{id}/update-profile-picture",
     *     summary="Update user profile picture",
     *     description="Update the profile picture for the authenticated user.",
     *     operationId="updateUserProfilePicture",
     *     tags={"Users"},
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
     *         description="ID of the user"
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="profile_picture", type="string", format="binary"),
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
     *             @OA\Property(property="message", type="string", example="Profile picture updated successfully"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="User not found"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object", example={"profile_picture": {"The profile_picture field is required."}}),
     *         )
     *     ),
     * )
     *
     * @param UpdateProfilePictureRequest $request
     * @param  int  $id
     * @return User
     */
    public function updateProfilePicture(UpdateProfilePictureRequest $request, int $id)
    {
        try {
            $response = $this->service->updateProfilePicture($request, $id);

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
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/users/{id}/notification",
     *     summary="Mark all user notifications as read",
     *     description="Mark all user notifications as read for the authenticated user.",
     *     operationId="markAllNotificationsAsRead",
     *     tags={"Users"},
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
     *         description="ID of the user"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="All notifications marked as read successfully"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="User not found"),
     *         )
     *     ),
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function updateNotification(int $id)
    {
        try {
            $response = $this->service->updateNotification($id);

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
     *     path="/api/v1/users/{id}",
     *     summary="Delete user",
     *     description="Delete a user by ID.",
     *     operationId="deleteUser",
     *     tags={"Users"},
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
     *         description="ID of the user"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="User deleted successfully"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
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

    public function resendVerificationEmail($userId)
    {
        try {
            $response = $this->service->resendVerificationEmail($userId);

            return success($response, __('api-response.success.resend_verification_email'));
        } catch (Exception $ex) {
            captureException($ex);

            return error($ex->getMessage(), new stdClass(), $ex->getCode() ?? 500);
        }
    }
}
