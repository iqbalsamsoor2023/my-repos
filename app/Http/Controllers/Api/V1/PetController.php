<?php

namespace App\Http\Controllers\Api\V1;

use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pet\StorePetRequest;
use App\Http\Requests\Pet\UpdatePetRequest;
use App\Http\Resources\Pet\PetCollection;
use App\Http\Resources\Pet\PetResource;
use App\Models\Pet;
use App\Services\PetService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PetController extends Controller
{
    protected $service;

    public function __construct(PetService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/pets",
     *     summary="Get Pets",
     *     description="Retrieve information about pets based on unit ID and user ID",
     *     tags={"Pets"},
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
     *                 @OA\Property(property="unit_id", type="integer", example=3),
     *                 @OA\Property(property="breed", type="string", example="dookie"),
     *                 @OA\Property(property="type", type="string", example="dog"),
     *                 @OA\Property(property="year", type="integer", example=2024),
     *                 @OA\Property(property="image_front_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_back_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_top_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_bottom_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_left_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_right_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="name", type="string", example="palmae"),
     *                 ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/pets?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/pets?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/pets"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=7),
     *             @OA\Property(property="total", type="integer", example=7),
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

            return success(new PetCollection($response));
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
     *     path="/api/v1/pets",
     *     summary="Store a new pet",
     *     tags={"Pets"},
     *     security={
     *         {"bearerAuth": {}}
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
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="unit_id", type="integer", format="int64"),
     *                 @OA\Property(property="user_id", type="integer", format="int64"),
     *                 @OA\Property(property="breed", type="string"),
     *                 @OA\Property(property="type", type="integer", format="int32", enum={1, 2}),
     *                 @OA\Property(property="year", type="integer", format="int32", minimum=1900, maximum=2025),
     *                 @OA\Property(property="image_front", type="string", format="binary"),
     *                 @OA\Property(property="image_right", type="string", format="binary"),
     *                 @OA\Property(property="image_back", type="string", format="binary"),
     *                 @OA\Property(property="image_left", type="string", format="binary"),
     *                 @OA\Property(property="image_top", type="string", format="binary"),
     *                 @OA\Property(property="image_bottom", type="string", format="binary"),
     *             ),
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="unit_id", type="integer", format="int64"),
     *                 @OA\Property(property="user_id", type="integer", format="int64"),
     *                 @OA\Property(property="breed", type="string"),
     *                 @OA\Property(property="type", type="integer", format="int32", enum={1, 2}),
     *                 @OA\Property(property="year", type="integer", format="int32", minimum=1900, maximum=2025),
     *                 @OA\Property(property="image_front", type="string"),
     *                 @OA\Property(property="image_right", type="string"),
     *                 @OA\Property(property="image_back", type="string"),
     *                 @OA\Property(property="image_left", type="string"),
     *                 @OA\Property(property="image_top", type="string"),
     *                 @OA\Property(property="image_bottom", type="string"),
     *             ),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pet stored successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Pet stored successfully"),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     * )
     *
     * @param StorePetRequest $request
     * @return Response
     */
    public function store(StorePetRequest $request)
    {
        try {
            $response = $this->service->create($request);

            return success(new PetResource($response));
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
     *     path="/api/v1/pets/{id}",
     *     summary="Show Pet",
     *     description="Retrieve details of a specific pet based on ID",
     *     tags={"Pets"},
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
     *         description="ID of the pet",
     *
     *         @OA\Schema(type="integer", example=123)
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
     *                 @OA\Property(property="unit_id", type="integer", example=3),
     *                 @OA\Property(property="user_id", type="integer", example=29),
     *                 @OA\Property(property="breed", type="string", example="dachshund"),
     *                 @OA\Property(property="type", type="string", example="dog"),
     *                 @OA\Property(property="year", type="string", example="2021"),
     *                 @OA\Property(property="image_front_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_back_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_top_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_bottom_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_left_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="image_right_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="name", type="string", example="palmae"),
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
     *     @OA\Response(
     *         response=404,
     *         description="No query results for model [App\\Models\\Pet] 111111"
     *     )
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function show(int $id)
    {
        try {
            $response = Pet::with('user', 'unit')->findOrFail($id);

            return success(new PetResource($response));
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
     *     path="/api/v1/pets/{id}",
     *     summary="Update an existing pet",
     *     tags={"Pets"},
     *     security={
     *         {"bearerAuth": {}}
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
     *         description="ID of the pet to update",
     *         required=true,
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="unit_id", type="integer", format="int64"),
     *                 @OA\Property(property="user_id", type="integer", format="int64"),
     *                 @OA\Property(property="breed", type="string"),
     *                 @OA\Property(property="type", type="integer", format="int32", enum={1, 2}),
     *                 @OA\Property(property="year", type="integer", format="int32", minimum=1900, maximum=2025),
     *                 @OA\Property(property="image_front", type="string", format="binary"),
     *                 @OA\Property(property="image_right", type="string", format="binary"),
     *                 @OA\Property(property="image_back", type="string", format="binary"),
     *                 @OA\Property(property="image_left", type="string", format="binary"),
     *                 @OA\Property(property="image_top", type="string", format="binary"),
     *                 @OA\Property(property="image_bottom", type="string", format="binary"),
     *             ),
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="unit_id", type="integer", format="int64"),
     *                 @OA\Property(property="user_id", type="integer", format="int64"),
     *                 @OA\Property(property="breed", type="string"),
     *                 @OA\Property(property="type", type="integer", format="int32", enum={1, 2}),
     *                 @OA\Property(property="year", type="integer", format="int32", minimum=1900, maximum=2025),
     *                 @OA\Property(property="image_front", type="string"),
     *                 @OA\Property(property="image_right", type="string"),
     *                 @OA\Property(property="image_back", type="string"),
     *                 @OA\Property(property="image_left", type="string"),
     *                 @OA\Property(property="image_top", type="string"),
     *                 @OA\Property(property="image_bottom", type="string"),
     *             ),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pet updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Pet updated successfully"),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     * )
     *
     * @param UpdatePetRequest $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdatePetRequest $request, int $id)
    {
        try {
            $response = $this->service->update($request, $id);

            return success(new PetResource($response));
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
     *     path="/api/v1/pets/{id}",
     *     summary="Delete Pet",
     *     description="Delete a specific pet based on ID",
     *     tags={"Pets"},
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
     *         description="ID of the pet",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="No Content - Pet successfully deleted",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Pet with the specified ID not found"
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
}
