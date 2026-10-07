<?php

namespace App\Http\Controllers\Api\V1;

use stdClass;
use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PreregisterVisitor\StorePreregisterVisitorRequest;
use App\Http\Requests\PreregisterVisitor\UpdatePreregisterVisitorRequest;
use App\Repositories\PreregisterVisitorRepository;
use Exception;
use Illuminate\Http\Request;

class PreregisterVisitorController extends Controller
{
    protected $preregisterVisitorRepository;

    public function __construct(PreregisterVisitorRepository $preregisterVisitorRepository)
    {
        $this->preregisterVisitorRepository = $preregisterVisitorRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/preregister-visitors",
     *     summary="Get Preregister Visitors",
     *     description="Retrieve information about pets based on unit ID and user ID",
     *     tags={"Preregister Visitors"},
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
     *     @OA\Parameter(
     *         name="visitor_code",
     *         in="query",
     *         description="Visitor code generated",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="mZvHKKm1mEPrnyhLVrq0")
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
     *          response="200",
     *          description="Success",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="current_page", type="integer", example=1),
     *                  @OA\Property(property="data", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=4106),
     *                      @OA\Property(property="visitor_id", type="integer", example=1162949),
     *                      @OA\Property(property="visitor_code", type="string", example="110NylA1NG5DW1dsI9Ef"),
     *                      @OA\Property(property="arrival_type", type="integer", example=1),
     *                      @OA\Property(property="vehicle_type", type="integer", example=1),
     *                      @OA\Property(property="visitor_purpose", type="string", example="Visitor Parking"),
     *                      @OA\Property(property="vehicle_plate_no", type="string", example="VIP 7777"),
     *                      @OA\Property(property="validity_start_date", type="string", example="2024-06-18 14:00:00"),
     *                      @OA\Property(property="validity_end_date", type="string", example="2024-07-31 00:00:00"),
     *                      @OA\Property(property="unit_id", type="integer", example=3),
     *                      @OA\Property(property="user_id", type="integer", example=29),
     *                      @OA\Property(property="is_multiple_entry", type="integer", example=1),
     *                      @OA\Property(property="is_qr_code_expired", type="integer", example=0),
     *                      @OA\Property(property="created_at", type="string", example="2024-06-18T05:29:18.000000Z"),
     *                      @OA\Property(property="updated_at", type="string", example="2024-06-18T05:29:18.000000Z"),
     *                      @OA\Property(property="qr_code_url", type="string", example="https://dashboard.mymooban.co.th/visitors/110NylA1NG5DW1dsI9Ef"),
     *                      @OA\Property(property="visitor", type="object",
     *                          @OA\Property(property="id", type="integer", example=1162949),
     *                          @OA\Property(property="name", type="string", example="wawa"),
     *                          @OA\Property(property="contact_no", type="string", example="0389890192"),
     *                          @OA\Property(property="id_type", type="integer", example=1),
     *                          @OA\Property(property="id_number", type="string", example="1890456712312"),
     *                      ),
     *                      @OA\Property(property="unit", type="object",
     *                          @OA\Property(property="id", type="integer", example=3),
     *                          @OA\Property(property="residence_id", type="integer", example=3019),
     *                          @OA\Property(property="invitation_code_owner", type="string", example="1030196257"),
     *                          @OA\Property(property="invitation_code_tenant", type="string", example="2030194233"),
     *                          @OA\Property(property="home_id", type="string", example="1030050301918199"),
     *                          @OA\Property(property="unit_size", type="string", example="1200"),
     *                          @OA\Property(property="myseevr_link", type="string", example="https://vr.realsee.jp/vr/ng0VJ8oMm1NMzlDa/jg6XpEkyjak2Jikh1hpTMlRS6LO0J4Q3/"),
     *                          @OA\Property(property="property_type", type="integer", example=4),
     *                          @OA\Property(property="unit_number", type="string", example="102/101"),
     *                          @OA\Property(property="street", type="string", example="MG1/1A"),
     *                          @OA\Property(property="floor", type="string", example=null),
     *                          @OA\Property(property="block", type="string", example=null),
     *                          @OA\Property(property="status", type="integer", example=1),
     *                          @OA\Property(property="move_in_at", type="string", example="2023-01-21"),
     *                          @OA\Property(property="created_at", type="string", example="2022-06-10T04:24:34.000000Z"),
     *                          @OA\Property(property="updated_at", type="string", example="2022-06-10T04:24:34.000000Z"),
     *                          @OA\Property(property="deleted_at", type="string", example=null),
     *                          @OA\Property(property="booking_form_pdf_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                          @OA\Property(property="house_contract_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                          @OA\Property(property="floor_plan_pdf_url", type="string", example=null),
     *                          @OA\Property(property="floor_plan_url", type="string", example=null),
     *                      ),
     *                  )),
     *                  @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/preregister-visitors?page=1"),
     *                  @OA\Property(property="from", type="integer", example=1),
     *                  @OA\Property(property="last_page", type="integer", example=9),
     *                  @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/preregister-visitors?page=9"),
     *                  @OA\Property(property="links", type="array", @OA\Items(
     *                      @OA\Property(property="url", type="string", example=null),
     *                      @OA\Property(property="label", type="string", example="Previous"),
     *                      @OA\Property(property="active", type="boolean", example=false),
     *                  )),
     *                  @OA\Property(property="next_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/preregister-visitors?page=2"),
     *                  @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/preregister-visitors"),
     *                  @OA\Property(property="per_page", type="integer", example=10),
     *                  @OA\Property(property="prev_page_url", type="string", example=null),
     *                  @OA\Property(property="to", type="integer", example=10),
     *                  @OA\Property(property="total", type="integer", example=81),
     *              ),
     *              @OA\Property(property="message", type="string", example="Success")
     *          )
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
            $response = $this->preregisterVisitorRepository->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return error($ex->getMessage(), new stdClass, $ex->getStatusCode());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/preregister-visitors",
     *     summary="Create pre-registered visitors",
     *     tags={"Preregister Visitors"},
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
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="name", type="string", maxLength=255),
     *                 @OA\Property(property="contact_no", type="string", maxLength=255),
     *                 @OA\Property(property="id_type", type="integer", enum={1, 2, 3}),
     *                 @OA\Property(property="id_number", type="string", maxLength=13),
     *                 @OA\Property(property="visitor_purpose", type="string", maxLength=255),
     *                 @OA\Property(property="arrival_type", type="integer", enum={1, 2}),
     *                 @OA\Property(property="vehicle_type", type="integer", enum={1, 2, 3, 4, 5, 6}),
     *                 @OA\Property(property="vehicle_plate_no", type="string", maxLength=20),
     *                 @OA\Property(property="is_multiple_entry", type="boolean"),
     *                 @OA\Property(property="validity_start_date", type="string", format="date", example="2024-01-18"),
     *                 @OA\Property(property="validity_end_date", type="string", format="date", example="2024-01-25"),
     *                 @OA\Property(property="unit_id", type="integer", format="int64"),
     *                 @OA\Property(property="user_id", type="integer", format="int64"),
     *                 @OA\Property(property="is_qr_code_expired", type="integer", enum={1, 0}),
     *             ),
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="name", type="string", maxLength=255),
     *                 @OA\Property(property="contact_no", type="string", maxLength=255),
     *                 @OA\Property(property="id_type", type="integer", enum={1, 2, 3}),
     *                 @OA\Property(property="id_number", type="string", maxLength=13),
     *                 @OA\Property(property="visitor_purpose", type="string", maxLength=255),
     *                 @OA\Property(property="arrival_type", type="integer", enum={1, 2}),
     *                 @OA\Property(property="vehicle_type", type="integer", enum={1, 2, 3, 4, 5, 6}),
     *                 @OA\Property(property="vehicle_plate_no", type="string", maxLength=20),
     *                 @OA\Property(property="is_multiple_entry", type="boolean"),
     *                 @OA\Property(property="validity_start_date", type="string", format="date", example="2024-01-18"),
     *                 @OA\Property(property="validity_end_date", type="string", format="date", example="2024-01-25"),
     *                 @OA\Property(property="unit_id", type="integer", format="int64"),
     *                 @OA\Property(property="user_id", type="integer", format="int64"),
     *                 @OA\Property(property="is_qr_code_expired", type="integer", enum={1, 0}),
     *             ),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Pre-registered visitors created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Pre-registered visitors created successfully"),
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
     *     )
     * )
     *
     * @param StorePreregisterVisitorRequest $request
     * @return Response
     */
    public function store(StorePreregisterVisitorRequest $request)
    {
        try {
            $response = $this->preregisterVisitorRepository->create($request);

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
     *     path="/api/v1/preregister-visitors/{id}",
     *     summary="Update is_qr_code_expired for pre-registered visitor",
     *     tags={"Preregister Visitors"},
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
     *         description="ID of the pre-registered visitor to update",
     *         required=true,
     *
     *         @OA\Schema(type="integer", format="int64")
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
     *
     *                 @OA\Property(property="is_qr_code_expired", type="integer", format="int32", enum={0, 1}),
     *             ),
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="is_qr_code_expired", type="integer", format="int32", enum={0, 1}),
     *             ),
     *         ),
     *
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Is_qr_code_expired updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Is_qr_code_expired updated successfully"),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pre-registered visitor not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     * )
     *
     * @param UpdatePreregisterVisitorRequest $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdatePreregisterVisitorRequest $request, int $id)
    {
        try {
            $response = $this->preregisterVisitorRepository->update($request, $id);

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
