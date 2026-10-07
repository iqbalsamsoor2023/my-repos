<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Resources\Transaction\TransactionResource;
use App\Services\TransactionService;
use Exception;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected $service;

    public function __construct(TransactionService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     *  * @OA\Get(
     *     path="/api/v1/transactions",
     *     summary="Get Transactions",
     *     description="Endpoint to retrieve transaction with optional parameters",
     *     tags={"Transactions"},
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
     *         name="invoice_id",
     *         in="query",
     *         description="ID of the invoice",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=90
     *     ),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Status of the transaction",
     *         required=false,
     *
     *         @OA\Schema(type="integer", enum={1, 2, 3}),
     *         example=1
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="ref_no", type="string", example="RE2021120001"),
     *                 @OA\Property(property="paid_amount", type="string", example="130.00"),
     *                 @OA\Property(property="invoice", type="object",
     *                     @OA\Property(property="items", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Water"),
     *                         @OA\Property(property="quantity", type="integer", example=1),
     *                         @OA\Property(property="price", type="string", example="30.00"),
     *                         @OA\Property(property="vat", type="string", example="0.00"),
     *                         @OA\Property(property="status", type="integer", example=2)
     *                     ))
     *                 ),
     *                 @OA\Property(property="payment_method", type="object",
     *                     @OA\Property(property="payment_mode", type="string", example="Cash Deposit")
     *                 )
     *             )),
     *             @OA\Property(property="http_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="status", type="boolean", example=true)
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

            return success(TransactionResource::collection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function show(Request $request, int $id)
    {
        try {
            $response = $this->service->show($request, $id);

            return success(new TransactionResource($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreTransactionRequest $request
     * @return Response
     */
    public function uploadSlip(StoreTransactionRequest $request)
    {
        try {
            $response = $this->service->uploadSlip($request);

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
     * Download receipt pdf file
     *
     * @param  int  $id
     * @return Response
     */
    public function downloadReceipt(int $id)
    {
        try {
            $response = $this->service->downloadReceipt($id);

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
