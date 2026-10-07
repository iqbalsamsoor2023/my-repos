<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\GetNotificationRequest;
use App\Http\Requests\Notification\GetUnreadNotificationRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     *  @OA\Get(
     *     path="/api/v1/notifications",
     *     summary="Get Notifications",
     *     description="Get a list of notifications based on parameters.",
     *     tags={"Notifications"},
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
     *         name="notifiable_id",
     *         in="query",
     *         description="ID of the user",
     *         required=true,
     *
     *         @OA\Schema(type="integer"),
     *         example=29
     *     ),
     *
     *     @OA\Parameter(
     *         name="notifiable_type",
     *         in="query",
     *         description="Type of notification",
     *         required=false,
     *
     *         @OA\Schema(type="string"),
     *         example="App\\Models\\User"
     *     ),
     *
     *     @OA\Parameter(
     *         name="read_at",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=0
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
     *                 @OA\Property(property="id", type="string", example="4af993fe-9146-4cef-839f-b03f998e2715"),
     *                 @OA\Property(property="type", type="string", example="App\Notifications\VisitorArrived"),
     *                 @OA\Property(property="notifiable_type", type="string", example="App\Models\User"),
     *                 @OA\Property(property="notifiable_id", type="integer", example=7801),
     *                 @OA\Property(property="data", type="object",
     *                     @OA\Property(property="model_type", type="string", example="App\Models\VisitorLog"),
     *                     @OA\Property(property="model_id", type="integer", example=1494787),
     *                     @OA\Property(property="message_title", type="string", example="You got a visitor for MI Garden, 705/123"),
     *                     @OA\Property(property="message_body", type="string", example="Your visitor - has arrived. Please confirmed your visitor for MI Garden."),
     *                     @OA\Property(property="residence_id", type="integer", example=3019),
     *                     @OA\Property(property="unit_id", type="integer", example=16),
     *                 ),
     *                 @OA\Property(property="read_at", type="string", example="2024-06-12 06:17:31"),
     *                 @OA\Property(property="created_at", type="string", example="2024-05-27 15:08:08"),
     *                 @OA\Property(property="updated_at", type="string", example="2024-06-11 23:17:31"),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/notifications?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/notifications?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/notifications"),
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
     *         response="401",
     *         description="Unauthorized",
     *     )
     * )
     *
     * @param  GetNotificationRequest  $request
     * @return Response
     */
    public function index(GetNotificationRequest $request)
    {
        try {
            $response = $this->service->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => $ex->getStatusCode()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     *  @OA\Get(
     *     path="/api/v1/notifications/unread-notification",
     *     summary="Get Notification Records",
     *     description="Endpoint to retrieve unread notification records for all modules",
     *     tags={"Notifications"},
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
     *         name="user_id",
     *         in="query",
     *         description="ID of the user",
     *         required=true,
     *
     *         @OA\Schema(type="integer"),
     *         example=29
     *     ),
     *
     *     @OA\Parameter(
     *         name="unit_id",
     *         in="query",
     *         description="ID of the unit",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=3
     *     ),
     *
     *     @OA\Parameter(
     *         name="module",
     *         in="query",
     *         description="module",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"Parcel", "Announcement", "Maintenance", "Event", "Support Ticket", "Visitor", "Notification"}),
     *         example="Parcel"
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="total_unread_notification", type="integer", example=18),
     *             @OA\Property(property="announcement", type="integer", example=0),
     *             @OA\Property(property="maintenance_pm", type="integer", example=4),
     *             @OA\Property(property="event", type="integer", example=0),
     *             @OA\Property(property="not_my_parcel", type="integer", example=1),
     *             @OA\Property(property="support_ticket_pm", type="integer", example=1),
     *             @OA\Property(property="announcement_event", type="integer", example=0),
     *             @OA\Property(property="announcement_event_developer", type="integer", example=0),
     *             @OA\Property(property="maintenance", type="integer", example=4),
     *             @OA\Property(property="parcel", type="integer", example=3),
     *             @OA\Property(property="visitor_record", type="integer", example=1),
     *             @OA\Property(property="support_ticket", type="integer", example=1),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     * )
     *
     * @param  GetUnreadNotificationRequest  $request
     * @return Response
     */
    public function unreadNotification(GetUnreadNotificationRequest $request)
    {
        try {
            return $this->service->unreadNotification($request);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => $ex->getStatusCode()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  string  $id
     * @return Response
     */
    public function update(string $id)
    {
        try {
            $response = $this->service->update($id);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => $ex->getStatusCode()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Sends a notification to a resident user regarding an incident report.
     *
     * @param  Request  $request  The HTTP request object containing the notification data.
     * @return Response A JSON response indicating whether the notification was successfully sent.
     */
    public function notifyResidentOfIncidentReport(Request $request)
    {
        try {
            $notificationData = [
                'id' => (int) $request->input('id'),
                'mmb_residence_id' => (int) $request->input('mmb_residence_id'),
                'mmb_unit_id' => (int) $request->input('mmb_unit_id'),
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'created_by' => (int) $request->input('created_by'),
                'recipient' => $request->input('recipient'),
            ];

            $response = $this->service->sendNotificationToResident($notificationData);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => $ex->getStatusCode()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage(), 'code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
