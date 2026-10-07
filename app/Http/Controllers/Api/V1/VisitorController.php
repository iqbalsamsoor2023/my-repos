<?php

namespace App\Http\Controllers\Api\V1;

use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisitingArrangement\UpdateVisitingArrangementRequest;
use App\Http\Requests\Visitor\CreateUpdateVisitorRequest;
use App\Http\Requests\Visitor\GetVisitorRequest;
use App\Http\Requests\Visitor\GetVisitorStatisticRequest;
use App\Http\Requests\Visitor\StoreFeedbackRequest;
use App\Http\Requests\Visitor\StoreVisitorRequest;
use App\Http\Requests\Visitor\UpdateVisitorRequest;
use App\Http\Resources\Visitor\VisitorLogCollection;
use App\Http\Resources\Visitor\VisitorLogResource;
use App\Services\VisitingArrangementService;
use App\Services\VisitorLogService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VisitorController extends Controller
{
    protected $service;

    protected $visitingArrangementService;

    public function __construct(VisitorLogService $service, VisitingArrangementService $visitingArrangementService)
    {
        $this->service = $service;
        $this->visitingArrangementService = $visitingArrangementService;
    }

    /**
     * Get statistic of the visitor.
     *
     * @param GetVisitorStatisticRequest $request
     * @return Response
     */
    public function statistic(GetVisitorStatisticRequest $request)
    {
        try {
            $response = $this->service->getStatistic($request);

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
     *     path="/api/v1/visitors",
     *     summary="Get visitors",
     *     description="Endpoint to retrieve visitors.",
     *     tags={"Visitors"},
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
     *         name="visitor_id",
     *         in="query",
     *         description="ID of the visitor",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="ID of the visit record",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="vehicle_plate_no",
     *         in="query",
     *         description="Vehicle plate number",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="visitor_code",
     *         in="query",
     *         description="Visitor code",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="residence_id",
     *         in="query",
     *         description="ID of the residence",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="name_vehicle_no",
     *         in="query",
     *         description="Name or vehicle number",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Name of the visitor",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Status of the visit",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="visit_at_range",
     *         in="query",
     *         description="Visit time range (format: Y-m-d H:i:s - Y-m-d H:i:s)",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="leave_at_range",
     *         in="query",
     *         description="Leave time range (format: Y-m-d H:i:s - Y-m-d H:i:s)",
     *
     *         @OA\Schema(type="string")
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
     *         description="Visitors retrieved successfully",
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index(GetVisitorRequest $request)
    {
        try {
            $response = $this->service->index($request);

            return success(new VisitorLogCollection($response));
        } catch (GeneralException $ex) {
            Log::error($ex->getMessage());

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            Log::error($ex->getMessage());

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/visitors",
     *     summary="Create visitor",
     *     description="Endpoint to create a visitor.",
     *     tags={"Visitors"},
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
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="visitor_id", type="integer"),
     *             @OA\Property(property="visitor_card_id", type="integer"),
     *             @OA\Property(property="visitor_purpose", type="string"),
     *             @OA\Property(property="visitor_generated_no", type="string"),
     *             @OA\Property(property="visitor_code", type="string"),
     *             @OA\Property(property="company_name", type="string"),
     *             @OA\Property(property="arrival_type", type="string"),
     *             @OA\Property(property="vehicle_type", type="string"),
     *             @OA\Property(property="vehicle_plate_no", type="string"),
     *             @OA\Property(property="arrival_time", type="string", format="date-time"),
     *             @OA\Property(property="temperature", type="number"),
     *             @OA\Property(property="passenger_count", type="integer"),
     *             @OA\Property(property="remark", type="string"),
     *             @OA\Property(property="is_allowed", type="boolean"),
     *             @OA\Property(property="blacklist_remark", type="string"),
     *             @OA\Property(property="is_pre_register", type="boolean"),
     *             @OA\Property(property="vehicle_info", type="string"),
     *             @OA\Property(property="residence_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="contact_no", type="string"),
     *             @OA\Property(property="id_type", type="string"),
     *             @OA\Property(property="id_number", type="string"),
     *             @OA\Property(property="id_image", type="string", format="binary"),
     *             @OA\Property(property="visitor_image", type="string", format="binary"),
     *             @OA\Property(property="vehicle_image", type="string", format="binary"),
     *             @OA\Property(property="esign_image", type="string", format="binary")
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="visitor_id", type="integer"),
     *                 @OA\Property(property="visitor_card_id", type="integer"),
     *                 @OA\Property(property="visitor_purpose", type="string"),
     *                 @OA\Property(property="visitor_generated_no", type="string"),
     *                 @OA\Property(property="visitor_code", type="string"),
     *                 @OA\Property(property="company_name", type="string"),
     *                 @OA\Property(property="arrival_type", type="string"),
     *                 @OA\Property(property="vehicle_type", type="string"),
     *                 @OA\Property(property="vehicle_plate_no", type="string"),
     *                 @OA\Property(property="arrival_time", type="string", format="date-time"),
     *                 @OA\Property(property="temperature", type="number"),
     *                 @OA\Property(property="passenger_count", type="integer"),
     *                 @OA\Property(property="remark", type="string"),
     *                 @OA\Property(property="is_allowed", type="boolean"),
     *                 @OA\Property(property="blacklist_remark", type="string"),
     *                 @OA\Property(property="is_pre_register", type="boolean"),
     *                 @OA\Property(property="vehicle_info", type="string"),
     *                 @OA\Property(property="residence_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="contact_no", type="string"),
     *                 @OA\Property(property="id_type", type="string"),
     *                 @OA\Property(property="id_number", type="string"),
     *                 @OA\Property(property="id_image", type="string", format="binary"),
     *                 @OA\Property(property="visitor_image", type="string", format="binary"),
     *                 @OA\Property(property="vehicle_image", type="string", format="binary"),
     *                 @OA\Property(property="esign_image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="201",
     *         description="Visitor created successfully"
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
     * @param StoreVisitorRequest $request
     * @return Response
     */
    public function store(StoreVisitorRequest $request)
    {
        $log = json_encode([
            'headers' => request()->header(),
            'body' => request()->input(),
        ]);

        DB::beginTransaction();
        try {
            $response = $this->service->create($request);

            DB::commit();

            return $response;
        } catch (GeneralException $ex) {
            DB::rollBack();
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            DB::rollBack();
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/v1/visitors/{id}",
     *     summary="Get a specific visitor",
     *     description="Retrieve details of a visitor by its ID",
     *     operationId="getVisitorById",
     *     tags={"Visitors"},
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
     *         required=true,
     *         description="ID of the visitor",
     *
     *         @OA\Schema(type="integer", example=447)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Visitor not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
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

            return success(new VisitorLogResource($response));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/visitors/{id}",
     *     summary="Update visitor",
     *     description="Endpoint to update a visitor.",
     *     tags={"Visitors"},
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
     *         description="ID of the visitor",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="leave_time", type="string", format="date-time"),
     *             @OA\Property(property="remark", type="string"),
     *             @OA\Property(property="visitor_card_id", type="integer")
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="leave_time", type="string", format="date-time"),
     *                 @OA\Property(property="remark", type="string"),
     *                 @OA\Property(property="visitor_card_id", type="integer")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Visitor updated successfully"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Visitor not found"
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Validation error"
     *     )
     * )
     *
     * @param UpdateVisitorRequest $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateVisitorRequest $request, int $id)
    {
        try {
            $response = $this->service->update($request, $id);

            return success($response);
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
     *     path="/api/v1/visitors/{id}/visiting-arrangements",
     *     summary="Update visiting arrangement",
     *     description="Endpoint to update a visiting arrangement.",
     *     tags={"Visitors"},
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
     *             @OA\Property(property="estamp_by_type", type="string"),
     *             @OA\Property(property="visiting_arrangement_id", type="integer"),
     *             @OA\Property(property="visitor_log_id", type="integer"),
     *             @OA\Property(property="unit_id", type="integer")
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
    public function updateVisitingArrangement(UpdateVisitingArrangementRequest $request, int $id)
    {
        try {
            $response = $this->visitingArrangementService->updateByVisitor($request, $id);

            return success($response);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['http_code' =>  $ex->getStatusCode(), 'message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/visitors/visitor-cards/{visitorCode}",
     *     summary="Update visitor by code",
     *     description="Endpoint to update a visitor using the visitor code.",
     *     tags={"Visitors"},
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
     *         name="visitorCode",
     *         in="path",
     *         required=true,
     *         description="Visitor code",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="leave_time", type="string", format="date-time"),
     *             @OA\Property(property="remark", type="string"),
     *             @OA\Property(property="visitor_card_id", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Visitor updated successfully"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Visitor not found"
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Validation error"
     *     )
     * )
     *
     * @param UpdateVisitorRequest $request
     * @param  string  $visitor_code
     * @return Response
     */
    public function updateByCode(UpdateVisitorRequest $request, string $visitor_code)
    {
        try {
            $response = $this->service->updateByVisitorCode($request, $visitor_code);

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
     *     path="/api/v1/visitors/visitor-cards/{visitor_card_id}",
     *     summary="Update visitor by card ID",
     *     description="Endpoint to update a visitor using the visitor card ID.",
     *     tags={"Visitors"},
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
     *         name="visitor_card_id",
     *         in="query",
     *         required=true,
     *         description="Visitor card ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="leave_time", type="string", format="date-time"),
     *             @OA\Property(property="remark", type="string"),
     *             @OA\Property(property="visitor_card_id", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Visitor updated successfully"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Visitor not found"
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Validation error"
     *     )
     * )
     *
     * @param UpdateVisitorRequest $request
     * @param  int  $visitor_card
     * @return Response
     */
    public function updateByVisitorCard(UpdateVisitorRequest $request, int $visitor_card)
    {
        try {
            $response = $this->service->updateByVisitorCard($request, $visitor_card);

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
     * Store a scan in or scan out in storage.
     *
     * @OA\Post(
     *     path="/api/v1/visitors/scan-qr-visitor",
     *     summary="Create or update visitor",
     *     description="Endpoint to create or update a visitor.",
     *     tags={"Visitors"},
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
     *                 @OA\Property(property="visitor_id", type="integer"),
     *                 @OA\Property(property="visitor_card_id", type="integer"),
     *                 @OA\Property(property="visitor_purpose", type="string"),
     *                 @OA\Property(property="visitor_generated_no", type="string"),
     *                 @OA\Property(property="visitor_code", type="string"),
     *                 @OA\Property(property="company_name", type="string"),
     *                 @OA\Property(property="arrival_type", type="string"),
     *                 @OA\Property(property="vehicle_type", type="string"),
     *                 @OA\Property(property="vehicle_plate_no", type="string"),
     *                 @OA\Property(property="arrival_time", type="string", format="date-time"),
     *                 @OA\Property(property="temperature", type="number"),
     *                 @OA\Property(property="passenger_count", type="integer"),
     *                 @OA\Property(property="remark", type="string"),
     *                 @OA\Property(property="is_allowed", type="boolean"),
     *                 @OA\Property(property="blacklist_remark", type="string"),
     *                 @OA\Property(property="is_pre_register", type="boolean"),
     *                 @OA\Property(property="vehicle_info", type="string"),
     *                 @OA\Property(property="residence_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="contact_no", type="string"),
     *                 @OA\Property(property="id_type", type="string"),
     *                 @OA\Property(property="id_number", type="string"),
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="visitor_id", type="integer"),
     *                 @OA\Property(property="visitor_card_id", type="integer"),
     *                 @OA\Property(property="visitor_purpose", type="string"),
     *                 @OA\Property(property="visitor_generated_no", type="string"),
     *                 @OA\Property(property="visitor_code", type="string"),
     *                 @OA\Property(property="company_name", type="string"),
     *                 @OA\Property(property="arrival_type", type="string"),
     *                 @OA\Property(property="vehicle_type", type="string"),
     *                 @OA\Property(property="vehicle_plate_no", type="string"),
     *                 @OA\Property(property="arrival_time", type="string", format="date-time"),
     *                 @OA\Property(property="temperature", type="number"),
     *                 @OA\Property(property="passenger_count", type="integer"),
     *                 @OA\Property(property="remark", type="string"),
     *                 @OA\Property(property="is_allowed", type="boolean"),
     *                 @OA\Property(property="blacklist_remark", type="string"),
     *                 @OA\Property(property="is_pre_register", type="boolean"),
     *                 @OA\Property(property="vehicle_info", type="string"),
     *                 @OA\Property(property="residence_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="contact_no", type="string"),
     *                 @OA\Property(property="id_type", type="string"),
     *                 @OA\Property(property="id_number", type="string"),
     *                 @OA\Property(property="id_image", type="string", format="binary"),
     *                 @OA\Property(property="visitor_image", type="string", format="binary"),
     *                 @OA\Property(property="vehicle_image", type="string", format="binary"),
     *                 @OA\Property(property="esign_image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Visitor created or updated successfully"
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
     * @param CreateUpdateVisitorRequest $request
     * @param StoreVisitorRequest $storeVisitorRequest
     * @param UpdateVisitorRequest $updateVisitorRequest
     * @return Response
     */
    public function scanInScanOut(CreateUpdateVisitorRequest $request, StoreVisitorRequest $storeVisitorRequest, UpdateVisitorRequest $updateVisitorRequest)
    {
        try {
            $response = $this->service->scanInScanOut($request, $storeVisitorRequest, $updateVisitorRequest);

            return $response;
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    public function getFeedbackType()
    {
        try {
            $feedbacks = [
                'remember_wrong' => 'Visitor remember wrong',
                'remember_wrong_th' => 'ผู้มาติดต่อแจ้งผิด',
                'press_wrong' => 'Security guard press wrong',
                'press_wrong_th' => 'รปภ. กดผิด',
                'impersonation' => 'Impersonation',
                'impersonation_th' => 'มีผู้แอบอ้าง',
                'other' => 'Other',
                'other_th' => 'อื่นๆ',
            ];

            return success($feedbacks);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    public function createFeedback(StoreFeedbackRequest $request)
    {
        try {
            $response = $this->service->storeFeedback($request->all());

            return $response;
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Get a listing of the prebook viistor resource.
     *
     * @param Request $request
     * @return Response
     */
    public function prebookVisitor(Request $request)
    {
        try {
            $response = $this->service->prebookVisitor($request);

            return $response;
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
