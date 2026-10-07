<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisitingArrangement\GetVisitingArrangementRequest;
use App\Http\Requests\VisitingArrangement\UpdateVisitingArrangementRequest;
use App\Http\Resources\Visitor\VisitingArrangementCollection;
use App\Services\VisitingArrangementService;
use Exception;

class VisitingArrangementController extends Controller
{
    protected $service;

    public function __construct(VisitingArrangementService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/visiting-arrangements",
     *     summary="Get visiting arrangements",
     *     description="Endpoint to retrieve visiting arrangements.",
     *     tags={"Visiting Arrangements"},
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
     *         name="visitor_log_id",
     *         in="query",
     *         description="ID of the visitor log",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="unit_id",
     *         in="query",
     *         description="ID of the unit",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="ID of the user",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="item_per_page",
     *         in="query",
     *         description="Number of items per page",
     *
     *         @OA\Schema(type="integer", default=10)
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
     *                 @OA\Property(property="id", type="integer", example=1712225),
     *                 @OA\Property(property="visitor_log_id", type="integer", example=1645475),
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="unit_id", type="integer", example=3),
     *                 @OA\Property(property="user_id", type="integer", example=29),
     *                 @OA\Property(property="estamp_by", type="object",
     *                     @OA\Property(property="id", type="integer", example=7558),
     *                     @OA\Property(property="country_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Pinnachan Dangulavanuch"),
     *                     @OA\Property(property="email", type="string", example="pinnachan@gmail.com"),
     *                     @OA\Property(property="email_verified_at", type="string", format="datetime", example="2024-04-30 21:32:24"),
     *                     @OA\Property(property="pdpa_agreed_at", type="string", format="datetime", example="2024-04-30 09:32:50"),
     *                 ),
     *                 @OA\Property(property="estamp_by_type", type="integer", example=3),
     *                 @OA\Property(property="status", type="integer", example=3),
     *                 @OA\Property(property="feedback_remark", type="integer", example="รปภ. กดผิด"),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2024-06-13T15:34:37.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2024-06-13T15:34:47.000000Z"),
     *                 @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                 @OA\Property(property="stamp_by_name", type="string", example="103005sc03019"),
     *                 @OA\Property(property="estamp_status", type="integer", example="Cancel by security guard"),
     *                 @OA\Property(property="user_estamp_status", type="integer", example="Cancel by security guard"),
     *                 @OA\Property(property="visitor_log", type="object",
     *                     @OA\Property(property="id", type="integer", example=1645475),
     *                     @OA\Property(property="visitor_id", type="integer", example=1319604),
     *                     @OA\Property(property="visitor_card_id", type="string", example=null),
     *                     @OA\Property(property="visitor_purpose", type="string", example="Receive & Delivery"),
     *                     @OA\Property(property="visitor_code", type="string", example="crFEr7Re0nUprmBo0XPQ"),
     *                     @OA\Property(property="visitor_generated_no", type="string", example="03019-240613-2655"),
     *                     @OA\Property(property="company_name", type="string", example=null),
     *                     @OA\Property(property="arrival_type", type="integer", example=1),
     *                     @OA\Property(property="vehicle_type", type="integer", example=1),
     *                     @OA\Property(property="vehicle_plate_no", type="string", example="hre5"),
     *                     @OA\Property(property="arrival_time", type="string", format="datetime", example="2024-06-13 22:34:37"),
     *                     @OA\Property(property="leave_time", type="string", format="datetime", example=null),
     *                     @OA\Property(property="passenger_count", type="integer", example=5),
     *                     @OA\Property(property="remark", type="string", example=null),
     *                     @OA\Property(property="blacklist_remark", type="string", example=null),
     *                     @OA\Property(property="is_allowed", type="integer", example=null),
     *                     @OA\Property(property="is_pre_register", type="integer", example=0),
     *                     @OA\Property(property="vehicle_info", type="string", example="{}"),
     *                     @OA\Property(property="created_at", type="string", format="datetime", example="2024-06-13T15:34:37.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime", example="2024-06-13T15:34:37.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                     @OA\Property(property="qr_code_url", type="string", example="https://dashboard.mymooban.co.th/visitors/crFEr7Re0nUprmBo0XPQ"),
     *                     @OA\Property(property="pdpa_status", type="string", example="none"),
     *                     @OA\Property(property="qr_visibility", type="integer", example=1),
     *                     @OA\Property(property="estamp_status", type="string", example="Partial Estamp"),
     *                     @OA\Property(property="esign_image_url", type="string", example=null),
     *                     @OA\Property(property="visitor", type="object",
     *                         @OA\Property(property="id", type="integer", example=1319604),
     *                         @OA\Property(property="name", type="string", example="gu"),
     *                         @OA\Property(property="contact_no", type="string", example=null),
     *                         @OA\Property(property="id_type", type="integer", example=null),
     *                         @OA\Property(property="id_number", type="string", example=null),
     *                         @OA\Property(property="created_at", type="string", format="datetime", example="2024-06-13T15:34:37.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="datetime", example="2024-06-13T15:34:37.000000Z"),
     *                         @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                     ),
     *                     @OA\Property(property="visitor_card", type="string", example=null),
     *                     @OA\Property(property="preregister_visitor", type="string", example=null),
     *                     @OA\Property(property="visiting_arrangements", type="array",
     *
     *                         @OA\Items(
     *
     *                             @OA\Property(property="id", type="integer", example=1712225),
     *                             @OA\Property(property="visitor_log_id", type="integer", example=1645475),
     *                             @OA\Property(property="residence_id", type="integer", example=3019),
     *                             @OA\Property(property="unit_id", type="integer", example=3),
     *                             @OA\Property(property="user_id", type="integer", example=29),
     *                             @OA\Property(property="estamp_by", type="integer", example=7558),
     *                             @OA\Property(property="estamp_by_type", type="integer", example=3),
     *                             @OA\Property(property="status", type="integer", example=3),
     *                             @OA\Property(property="feedback_remark", type="string", example="รปภ. กดผิด"),
     *                             @OA\Property(property="created_at", type="string", format="datetime", example="2024-06-13T15:34:37.000000Z"),
     *                             @OA\Property(property="updated_at", type="string", format="datetime", example="2024-06-13T15:34:47.000000Z"),
     *                            @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                         )
     *                     ),
     *                 ),
     *                 @OA\Property(property="visitor_remarks", type="array",
     *
     *                    @OA\Items(
     *
     *                        @OA\Property(property="id", type="integer", example=10),
     *                        @OA\Property(property="residence_id", type="integer", example=3019),
     *                        @OA\Property(property="remark", type="string", example="มีตราประทับจอดฟรี 3 ชั่วโมง\nบัตรหายปรับ 500 บาท"),
     *                        @OA\Property(property="created_at", type="string", example="2023-09-21T15:32:45.000000Z"),
     *                        @OA\Property(property="updated_at", type="string", example="2023-09-21T15:32:45.000000Z"),
     *                    ),
     *                 ),
     *                 @OA\Property(property="residence", type="object",
     *                     @OA\Property(property="id", type="integer", example=3019),
     *                     @OA\Property(property="name", type="string", example="Mi Garden"),
     *                     @OA\Property(property="media", type="array",
     *
     *                        @OA\Items(
     *                        )
     *                     ),
     *                 ),
     *             )),
     *
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/visiting-arrangements?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=21),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/visiting-arrangements?page=21"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/visiting-arrangements?page=2"),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/visiting-arrangements"),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=206),
     *         ),
     *          @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index(GetVisitingArrangementRequest $request)
    {
        try {
            $response = $this->service->index($request);

            return success(new VisitingArrangementCollection($response));
        } catch (GeneralException $ex) {
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
     *     path="/api/v1/visiting-arrangements/{id}",
     *     summary="Update visiting arrangement",
     *     description="Endpoint to update a visiting arrangement.",
     *     tags={"Visiting Arrangements"},
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
     *         description="ID of the visiting arrangement",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="estamp_by", type="string"),
     *             @OA\Property(property="feedback_remark", type="string"),
     *             @OA\Property(property="estamp_by_type", type="string")
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="estamp_by", type="string"),
     *                 @OA\Property(property="feedback_remark", type="string"),
     *                 @OA\Property(property="estamp_by_type", type="string")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Visiting arrangement updated successfully"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Visiting arrangement not found"
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Validation error"
     *     )
     * )
     *
     * @param UpdateVisitingArrangementRequest $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateVisitingArrangementRequest $request, int $id)
    {
        try {
            $response = $this->service->update($request, $id);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
