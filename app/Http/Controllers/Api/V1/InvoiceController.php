<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\GetTotalUnpaidInvoiceRequest;
use App\Http\Resources\Invoice\InvoiceCollection;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Services\InvoiceService;
use Exception;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected $service;

    public function __construct(InvoiceService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     *  * @OA\Get(
     *     path="/api/v1/invoices",
     *     summary="Get Invoices",
     *     description="Endpoint to retrieve invoices with optional parameters",
     *     tags={"Invoices"},
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
     *         in="query",
     *         description="ID of the invoice",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=5637
     *     ),
     *
     *     @OA\Parameter(
     *         name="payer_unit_id",
     *         in="query",
     *         description="ID of the payer's unit",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=16
     *     ),
     *
     *     @OA\Parameter(
     *         name="payer_id",
     *         in="query",
     *         description="ID of the payer",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=7801
     *     ),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Status of the invoice",
     *         required=false,
     *
     *         @OA\Schema(type="integer", enum={1, 2, 3, 4, 5, 6}),
     *         example=1
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
     *                 @OA\Property(property="id", type="integer", example=5637),
     *                 @OA\Property(property="invoice_no", type="string", example="INV2024070002"),
     *                 @OA\Property(property="bill_no", type="string", example="001"),
     *                 @OA\Property(property="bill_date", type="string", example="2024-07-01"),
     *                 @OA\Property(property="due_date", type="string", example="2024-07-31"),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(property="status_name", type="string", example="Unpaid"),
     *                 @OA\Property(property="total_amount", type="string", example="53242.00"),
     *                 @OA\Property(property="amount_due", type="string", example="53242.00"),
     *                 @OA\Property(property="remark", type="string", example="จ่ายด้วยไวๆ"),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2024-07-17 07:11:05"),
     *                 @OA\Property(property="items", type="array",
     *
     *                      @OA\Items(
     *
     *                          @OA\Property(property="id", type="integer", example=5880),
     *                          @OA\Property(property="name", type="string", example="ค่าน้ำ"),
     *                          @OA\Property(property="quantity", type="integer", example=1),
     *                          @OA\Property(property="price", type="number", format="double", example=10000.00),
     *                          @OA\Property(property="status", type="integer", example=1),
     *                      )
     *                 ),
     *                 @OA\Property(property="transactions", type="array",
     *
     *                      @OA\Items(
     *
     *                          @OA\Property(property="ref_no", type="string", example="RE2021090001"),
     *                      )
     *                 ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/invoices?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/invoices?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/invoices"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=6),
     *             @OA\Property(property="total", type="integer", example=6),
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
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Invoice not found"
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

            return success(new InvoiceCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     *  @OA\Get(
     *     path="/api/v1/invoices/{id}",
     *     summary="Get Invoice by ID",
     *     description="Endpoint to retrieve an invoice by ID",
     *     tags={"Invoices"},
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
     *         description="ID of the invoice",
     *         required=true,
     *
     *         @OA\Schema(type="integer"),
     *         example=123
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *            @OA\Property(property="data", type="object",
     *               @OA\Property(property="id", type="integer", example=1),
     *               @OA\Property(property="invoice_no", type="string", example="INV2021090002"),
     *               @OA\Property(property="bill_no", type="string", example="Test1113"),
     *               @OA\Property(property="due_date", type="string", example="2021-10-30"),
     *               @OA\Property(property="status_name", type="string", example="Paid"),
     *               @OA\Property(property="total_amount", type="string", example="1300.00 Baht"),
     *               @OA\Property(property="amount_due", type="string", example="0.00 Baht"),
     *               @OA\Property(property="remark", type="string", example="ccc"),
     *               @OA\Property(property="created_at", type="string", format="datetime", example="2021-09-30 19:59:07"),
     *               @OA\Property(property="items", type="array",
     *
     *                   @OA\Items(
     *
     *                          @OA\Property(property="id", type="integer", example=5),
     *                          @OA\Property(property="name", type="string", example="Insurance"),
     *                          @OA\Property(property="quantity", type="integer", example=1),
     *                          @OA\Property(property="price", type="number", format="double", example=300.00),
     *                          @OA\Property(property="status", type="integer", example=2),
     *                      )
     *               ),
     *               @OA\Property(property="transactions", type="array",
     *
     *                   @OA\Items(
     *
     *                          @OA\Property(property="ref_no", type="string", example="RE2021090001"),
     *                   )
     *               )
     *             ),
     *             @OA\Property(property="http_code", type="number", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="status", type="boolean", example=true)
     *            )),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Invoice not found"
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

            return success(new InvoiceResource($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Download invoice pdf file
     *
     *
     * @OA\Get(
     *     path="/api/v1/invoices/{id}/invoice-file",
     *     summary="Download Invoice",
     *     description="Endpoint to download an invoice by ID",
     *     tags={"Invoices"},
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
     *         description="ID of the invoice to download",
     *         required=true,
     *
     *         @OA\Schema(type="integer"),
     *         example=123
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful download",
     *
     *         @OA\Header(
     *             header="Content-Disposition",
     *             description="Attachment; filename=invoice.pdf",
     *
     *             @OA\Schema(type="string")
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             example="binary data of the invoice PDF file"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Invoice not found"
     *     )
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function downloadInvoice(int $id)
    {
        try {
            $response = $this->service->downloadInvoice($id);

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
     * Download invoice pdf file
     *
     *
     * @OA\Get(
     *     path="/api/v1/invoices/total-unpaid-invoice",
     *     summary="Total Unpaid Invoice",
     *     description="Endpoint to get total of unpaid invoice",
     *     tags={"Invoices"},
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
     *         name="payer_id",
     *         in="path",
     *         description="ID of the user that pay the bill",
     *         required=true,
     *
     *         @OA\Schema(type="integer"),
     *         example=7801
     *     ),
     *
     *     @OA\Parameter(
     *         name="payer_unit_id",
     *         in="path",
     *         description="ID unit of the user",
     *         required=true,
     *
     *         @OA\Schema(type="integer"),
     *         example=16
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *            @OA\Property(property="count_unpaid_bill", type="integer", example=4)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication"
     *     )
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function totalUnpaidInvoice(GetTotalUnpaidInvoiceRequest $request)
    {
        try {
            $response = $this->service->totalUnpaidInvoice($request);

            return response()->json(['count_unpaid_bill' => $response], 200);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
