<?php

namespace App\Http\Controllers\Api\V1;

use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmergencyContact\StoreEmergencyContactRequest;
use App\Services\EmergencyContactService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmergencyContactController extends Controller
{
    protected $service;

    public function __construct(EmergencyContactService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/emergency-contacts",
     *     summary="Get Emergency Contact Information",
     *     description="Retrieve information about emergency contacts",
     *     tags={"Emergency Contact"},
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
     *         name="district_id",
     *         in="query",
     *         description="ID of the district",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=10)
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
     *                 @OA\Property(property="id", type="integer", example=16),
     *                 @OA\Property(property="department_type", type="string", example="Fire Station"),
     *                 @OA\Property(property="name", type="string", example="สถานีดับเพลิงบางชัน (Bangchan Fire Station)"),
     *                 @OA\Property(property="contact_no", type="string", example="000000000"),
     *                 @OA\Property(property="coverage_mode", type="string", example="Province"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", example="2019-01-08T17:46:22.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2019-08-17T19:47:27.000000Z"),
     *                 @OA\Property(property="deleted_at", type="string", example=null),
     *                 @OA\Property(property="district_emergency_contacts", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=18),
     *                     @OA\Property(property="emergency_contact_id", type="integer", example=16),
     *                     @OA\Property(property="thailand_district_id", type="integer", example=10),
     *                     @OA\Property(property="created_at", type="string", format="datetime", example="2019-01-08T17:46:22.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime", example="2019-08-17T19:47:27.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                  ),
     *                 ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/emergency-contacts?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/emergency-contacts?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/emergency-contacts"),
     *             @OA\Property(property="per_page", type="integer", example=25),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=11),
     *             @OA\Property(property="total", type="integer", example=11),
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
            $response = $this->service->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     *      @OA\Post(
     *      path="/api/v1/emergency-contacts",
     *      operationId="createEmergencyContact",
     *      tags={"Emergency Contact"},
     *      summary="Create a new emergency contact",
     *      description="Create a new emergency contact with JSON or multipart format",
     *      security={
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
     *      @OA\RequestBody(
     *          required=true,
     *          description="Emergency contact data",
     *
     *           @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="department_type", type="string", enum={"Hospital", "Police", "Foundation", "Fire Station"}, example="Hospital"),
     *                 @OA\Property(property="name", type="string", maxLength=255, example="ExampleName"),
     *                 @OA\Property(property="contact_no", type="string", maxLength=20, example="1234567890"),
     *                 @OA\Property(property="coverage_mode", type="integer", enum={1, 2}, example=1),
     *                 @OA\Property(property="is_active", type="boolean", enum={true, false}, example=true),
     *             ),
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="department_type", type="string", enum={"Hospital", "Police", "Foundation", "Fire Station"}, example="Hospital"),
     *                 @OA\Property(property="name", type="string", maxLength=255, example="ExampleName"),
     *                 @OA\Property(property="contact_no", type="string", maxLength=20, example="1234567890"),
     *                 @OA\Property(property="coverage_mode", type="integer", enum={1, 2}, example=1),
     *                 @OA\Property(property="is_active", type="boolean", enum={true, false}, example=true),
     *                 @OA\Property(property="file", type="string", format="binary"),
     *             ),
     *         ),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="Success",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="department_type", type="string", example="Hospital"),
     *                  @OA\Property(property="name", type="string", example="test"),
     *                  @OA\Property(property="contact_no", type="string", example="312312"),
     *                  @OA\Property(property="coverage_mode", type="string", example="Province"),
     *                  @OA\Property(property="is_active", type="string", example="1"),
     *                  @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-17T00:42:15.000000Z"),
     *                  @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-17T00:42:15.000000Z"),
     *                  @OA\Property(property="id", type="integer", example=1624),
     *              ),
     *              @OA\Property(property="message", type="string", example="Success"),
     *          ),
     *      ),
     *
     *      @OA\Response(response="400", description="Bad request"),
     *      @OA\Response(
     *          response="422",
     *              description="Validation error",
     *      ),
     * )
     *
     * @param StoreEmergencyContactRequest $request
     * @return Response
     */
    public function store(StoreEmergencyContactRequest $request)
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
     *     path="/api/v1/emergency-contacts/{id}",
     *     summary="Show Emergency Contact Information",
     *     description="Show information for a specific emergency contact based on ID",
     *     tags={"Emergency Contact"},
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
     *         description="ID of the emergency contact",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=16)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Emergency Contact not found"
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

            return success($response);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
