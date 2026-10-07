<?php

namespace App\Http\Controllers\Api\V1;

use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\StoreRsvpRequest;
use App\Http\Resources\Event\EventCollection;
use App\Http\Resources\Event\EventResource;
use App\Services\EventService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EventController extends Controller
{
    protected $service;

    public function __construct(EventService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/events",
     *     summary="Get a list of events",
     *     description="Retrieve a paginated list of events.",
     *     operationId="getEventList",
     *     tags={"Events"},
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
     *         name="residence_id",
     *         in="query",
     *         description="ID of the residence",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=3019
     *     ),
     *
     *     @OA\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="ID of the role",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="integer",
     *             enum={2, 7, 8}
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *
     *                 @OA\Property(property="data", type="object",
     *                     properties={
     *                         @OA\Property(property="current_page", type="integer"),
     *                         @OA\Property(property="data", type="array",
     *
     *                             @OA\Items(
     *                                 type="object",
     *                                 properties={
     *
     *                                     @OA\Property(property="id", type="integer", example=25),
     *                                     @OA\Property(property="residence_id", type="integer", example=3019),
     *                                     @OA\Property(property="title", type="string", example="700 X 300"),
     *                                     @OA\Property(property="description", type="string", example="700 X 300"),
     *                                     @OA\Property(property="start_at", type="string", format="date-time", example="2023-02-23 16:15:00"),
     *                                     @OA\Property(property="end_at", type="string", format="date-time", example="2023-02-28 23:00:00"),
     *                                     @OA\Property(property="is_active", type="integer", example=1),
     *                                     @OA\Property(property="is_cancel", type="integer", example=0),
     *                                     @OA\Property(property="created_by", type="integer", example=22),
     *                                     @OA\Property(property="updated_by", type="integer", example=22),
     *                                     @OA\Property(property="image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-02-22T16:11:43.000000Z"),
     *                                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-02-22T16:11:43.000000Z"),
     *                                     @OA\Property(property="rsvp_status", type="integer", example=0),
     *                                     @OA\Property(property="read_status", type="integer", example=1),
     *                                     @OA\Property(property="rsvps", type="array",
     *
     *                                         @OA\Items(
     *                                             type="object",
     *                                             properties={
     *
     *                                                 @OA\Property(property="id", type="integer", example=42),
     *                                                 @OA\Property(property="event_id", type="integer", example=25),
     *                                                 @OA\Property(property="user_id", type="integer", example=7801),
     *                                                 @OA\Property(property="is_going", type="integer", example=0),
     *                                                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-05 00:15:52"),
     *                                                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-05 00:19:26"),
     *                                                 @OA\Property(property="deleted_at", type="string", format="date-time", example=null),
     *                                             }
     *                                         ),
     *                                     ),
     *                                 },
     *                             ),
     *                         ),
     *                         @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/events?page=1"),
     *                         @OA\Property(property="from", type="integer", example=1),
     *                         @OA\Property(property="last_page", type="integer", example=1),
     *                         @OA\Property(property="last_page_url", type="string",  example="https://dashboard.mymooban.co.th/api/v1/events?page=1"),
     *                         @OA\Property(property="links", type="array",
     *
     *                             @OA\Items(
     *                                 type="object",
     *                                 properties={
     *
     *                                     @OA\Property(property="url", type="string", example=null),
     *                                     @OA\Property(property="label", type="string", example="Previous"),
     *                                     @OA\Property(property="active", type="boolean"),
     *                                 }
     *                             ),
     *                         ),
     *                         @OA\Property(property="next_page_url", type="string", example=null),
     *                         @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/events"),
     *                         @OA\Property(property="per_page", type="integer", example=25),
     *                         @OA\Property(property="prev_page_url", type="string", example=null),
     *                         @OA\Property(property="to", type="integer", example=1),
     *                         @OA\Property(property="total", type="integer", example=1),
     *                     }
     *                 ),
     *                 @OA\Property(property="message", type="string"),
     *             },
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return success(new EventCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/events",
     *     operationId="createEvent",
     *     tags={"Events"},
     *     summary="Create a new event",
     *     description="Creates a new event with the specified details",
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
     *                 required={"residence_id", "title", "description", "start_at", "end_at", "is_active", "is_cancel", "created_by"},
     *
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="title", type="string", example="New Event Title"),
     *                 @OA\Property(property="description", type="string", example="Description of the new event"),
     *                 @OA\Property(property="start_at", type="string", format="date-time", example="2022-01-01 12:00:00"),
     *                 @OA\Property(property="end_at", type="string", format="date-time", example="2022-01-01 14:00:00"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="is_cancel", type="integer", example=0),
     *                 @OA\Property(property="created_by", type="integer", example=3),
     *                 @OA\Property(property="image", type="string"),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"residence_id", "title", "description", "start_at", "end_at", "is_active", "is_cancel", "created_by", "image"},
     *
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="title", type="string", example="New Event Title"),
     *                 @OA\Property(property="description", type="string", example="Description of the new event"),
     *                 @OA\Property(property="start_at", type="string", format="date-time", example="2022-01-01 12:00:00"),
     *                 @OA\Property(property="end_at", type="string", format="date-time", example="2022-01-01 14:00:00"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="is_cancel", type="integer", example=0),
     *                 @OA\Property(property="created_by", type="integer", example=3),
     *                 @OA\Property(property="image", type="string", format="binary"),
     *             ),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Event created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="residence_id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="start_at", type="string", format="date-time"),
     *                 @OA\Property(property="end_at", type="string", format="date-time"),
     *                 @OA\Property(property="is_active", type="integer"),
     *                 @OA\Property(property="is_cancel", type="integer"),
     *                 @OA\Property(property="created_by", type="integer"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="residence", type="object",
     *                  @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="name_th", type="string"),
     *             @OA\Property(property="mooban_type", type="string"),
     *             @OA\Property(property="sub_type", type="integer"),
     *             @OA\Property(property="completion_year", type="integer"),
     *             @OA\Property(property="latitude", type="number"),
     *             @OA\Property(property="longitude", type="number"),
     *             @OA\Property(property="subdistrict_id", type="integer"),
     *             @OA\Property(property="developer_id", type="integer"),
     *             @OA\Property(property="developer_user_id", type="integer"),
     *             @OA\Property(property="property_management_type", type="integer"),
     *             @OA\Property(property="property_management_id", type="integer"),
     *             @OA\Property(property="property_management_user_id", type="integer"),
     *             @OA\Property(property="receptionist_user_id", type="integer"),
     *             @OA\Property(property="accountant_user_id", type="integer"),
     *             @OA\Property(property="sgoc_company_id", type="integer"),
     *             @OA\Property(property="sgoc_residence_guard_user_id", type="integer"),
     *             @OA\Property(property="insurance_company_id", type="integer"),
     *             @OA\Property(property="company_id", type="integer"),
     *             @OA\Property(property="is_active", type="integer"),
     *             @OA\Property(property="is_demo", type="integer"),
     *             @OA\Property(property="residence_activation_status_id", type="integer"),
     *             @OA\Property(property="internet_provider_id", type="integer"),
     *             @OA\Property(property="guard_house_entry_number", type="string"),
     *             @OA\Property(property="guard_house_lane_type", type="string"),
     *             @OA\Property(property="subscription_start_date", type="string", format="date"),
     *             @OA\Property(property="subscription_end_date", type="string", format="date"),
     *             @OA\Property(property="support_ticket_status", type="integer"),
     *             @OA\Property(property="appointment_datetime_status", type="integer"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="deleted_at", type="string", format="date-time"),
     *             @OA\Property(property="image_url", type="string"),
     *             @OA\Property(property="media", type="array", @OA\Items(type="object")),
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Success"),
     *         )
     *     ),
     *
     *      @OA\Response(response="400", description="Bad request"),
     *      @OA\Response(
     *          response="422",
     *          description="Validation error"
     *      ),
     * )
     */
    public function store(StoreEventRequest $request)
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
     * @OA\Get(
     *     path="/api/v1/events/{id}",
     *     summary="Get details of a specific event",
     *     description="Retrieve details of a specific event based on its ID.",
     *     operationId="getEventDetails",
     *     tags={"Events"},
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
     *         description="ID of the event",
     *         required=true,
     *
     *         @OA\Schema(
     *              type="integer",
     *              example=21
     *          )
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
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="title", type="string", example="Create Landscape Competition"),
     *                 @OA\Property(property="description", type="string", example="Create Landscape Competition\r\nhttps://www.youtube.com/watch?v=4GQLF7uJCcM"),
     *                 @OA\Property(property="start_at", type="string", format="date-time", example="2022-09-10 08:15:00"),
     *                 @OA\Property(property="end_at", type="string", format="date-time", example="2022-09-10 23:00:00"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="is_cancel", type="integer", example=0),
     *                 @OA\Property(property="created_by", type="integer", example=22),
     *                 @OA\Property(property="updated_by", type="integer", example=22),
     *                 @OA\Property(property="image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2022-09-06T10:09:45.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2022-09-06T10:09:45.000000Z"),
     *                 @OA\Property(property="rsvps", type="array",
     *
     *                      @OA\Items(type="object",properties={
     *
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="event_id", type="integer"),
     *                          @OA\Property(property="user_id", type="integer"),
     *                          @OA\Property(property="is_going", type="integer"),
     *                          @OA\Property(property="created_at", type="string", format="date-time"),
     *                          @OA\Property(property="updated_at", type="string", format="date-time"),
     *                          @OA\Property(property="deleted_at", type="string", format="date-time", example=null),
     *                       }
     *                  ),
     *               ),
     *             ),
     *             @OA\Property(property="message", type="string", example="success"),
     *         ),
     *     ),
     *
     *     @OA\Response(response="400", description="Bad request"),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     * )
     */
    public function show(int $id)
    {
        try {
            $response = $this->service->show($id);

            return success(new EventResource($response));
        } catch (ModelNotFoundException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/events/{id}/rsvps",
     *     operationId="rsvpEvent",
     *     tags={"Events"},
     *     summary="RSVP to an event",
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
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the event",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\MediaType(
     *              mediaType="application/json",
     *
     *              @OA\Schema(required={"id", "user_id", "is_going"},
     *
     *                  @OA\Property(property="event_id", type="integer", example=1),
     *                  @OA\Property(property="user_id", type="integer", example=2),
     *                  @OA\Property(property="is_going", type="integer", enum={0, 1}, example=1),
     *              ),
     *          ),
     *
     *          @OA\MediaType(
     *              mediaType="application/x-www-form-urlencoded",
     *
     *              @OA\Schema(
     *                  required={"id", "user_id", "is_going"},
     *
     *                  @OA\Property(property="event_id", type="integer", example=1),
     *                  @OA\Property(property="user_id", type="integer", example=2),
     *                  @OA\Property(property="is_going", type="integer", enum={0, 1}, example=1),
     *              ),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="event_id", type="integer", example=34),
     *                  @OA\Property(property="user_id", type="string", example="1"),
     *                  @OA\Property(property="is_going", type="string", example="1"),
     *                  @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-03T09:00:10.079844Z"),
     *                  @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-03T09:00:10.079844Z"),
     *                  @OA\Property(property="id", type="integer", example=6),
     *              ),
     *              @OA\Property(property="message", type="string", example="Success")
     *          )
     *      ),
     *
     *      @OA\Response(response="400", description="Bad request"),
     *      @OA\Response(response="422", description="Validation error"),
     * )
     */
    public function rsvp(StoreRsvpRequest $request, int $id)
    {
        try {
            $response = $this->service->rsvp($request, $id);

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
