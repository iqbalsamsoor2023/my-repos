<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserHealth\StoreUserHealthRequest;
use App\Http\Requests\UserHealth\UpdateUserHealthRequest;
use App\Http\Resources\UserHealthResource;
use App\Models\UserHealth;
use App\Services\UserHealthService;
use Exception;
use stdClass;

class UserHealthController extends Controller
{
    protected $service;

    public function __construct(UserHealthService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/user-healths",
     *     summary="Add user health information",
     *     description="Endpoint to add health information for a user.",
     *     tags={"User Healths"},
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
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="user_id", type="integer"),
     *                 *     @OA\Property(property="blood_type", type="string", enum={"O-", "O+", "A+", "A-", "B+", "B-", "AB-", "AB+"}),
     *                 @OA\Property(property="height", type="number"),
     *                 @OA\Property(property="weight", type="number"),
     *                 @OA\Property(
     *                     property="health_questionnaire_answers",
     *                     type="array",
     *
     *                     @OA\Items(type="string")
     *                 )
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="user_id", type="integer"),
     *                 *     @OA\Property(property="blood_type", type="string", enum={"O-", "O+", "A+", "A-", "B+", "B-", "AB-", "AB+"}),
     *                 @OA\Property(property="height", type="number"),
     *                 @OA\Property(property="weight", type="number"),
     *                 @OA\Property(
     *                     property="health_questionnaire_answers",
     *                     type="array",
     *
     *                     @OA\Items(type="string")
     *                 ),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Health information added successfully"
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
     * @param StoreUserHealthRequest $request
     * @return Response
     */
    public function store(StoreUserHealthRequest $request)
    {
        try {
            $response = $this->service->create($request);

            return success(new UserHealthResource($response));
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
     *     path="/api/v1/user-healths/{user_id}",
     *     summary="Get user health information",
     *     description="Endpoint to retrieve health information for a specific user.",
     *     tags={"User Healths"},
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
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="ID of the user",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="User health information retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="blood_type", type="string"),
     *             @OA\Property(property="height", type="number"),
     *             @OA\Property(property="weight", type="number"),
     *             @OA\Property(
     *                 property="health_questionnaire_answers",
     *                 type="array",
     *
     *                 @OA\Items(type="string")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="User health record not found"
     *     )
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function show(int $user_id)
    {
        try {
            $response = UserHealth::with('user')->where('user_id', $user_id)->first();

            if (! $response) {
                return success(new stdClass, 'User health record not created yet', 404);
            }

            return success(new UserHealthResource($response));
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
     *     path="/api/v1/user-healths/{id}",
     *     summary="Update user health information",
     *     description="Endpoint to update health information for a user.",
     *     tags={"User Healths"},
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
     *         description="ID of the user health record",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="blood_type", type="string", enum={"O-", "O+", "A+", "A-", "B+", "B-", "AB-", "AB+"}),
     *             @OA\Property(property="height", type="number"),
     *             @OA\Property(property="weight", type="number"),
     *             @OA\Property(
     *                 property="health_questionnaire_answers",
     *                 type="array",
     *
     *                 @OA\Items(type="string")
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="blood_type", type="string", enum={"O-", "O+", "A+", "A-", "B+", "B-", "AB-", "AB+"}),
     *                 @OA\Property(property="height", type="number"),
     *                 @OA\Property(property="weight", type="number"),
     *                 @OA\Property(
     *                     property="health_questionnaire_answers",
     *                     type="array",
     *
     *                     @OA\Items(type="string")
     *                 ),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Health information updated successfully"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="User health record not found"
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Validation error"
     *     )
     * )
     *
     * @param UpdateUserHealthRequest $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateUserHealthRequest $request, int $id)
    {
        try {
            $response = $this->service->update($request, $id);

            return success(new UserHealthResource($response));
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
