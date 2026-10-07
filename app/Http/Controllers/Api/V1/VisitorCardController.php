<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Repositories\VisitorCardRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class VisitorCardController extends Controller
{
    protected $visitorCardRepository;

    public function __construct(VisitorCardRepository $visitorCardRepository)
    {
        $this->visitorCardRepository = $visitorCardRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/visitor-cards",
     *     summary="Get Visitor Cards",
     *     description="Retrieve visitor cards",
     *     tags={"Visitor Cards"},
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
     *      @OA\Parameter(
     *          name="visitor_card_no",
     *          in="query",
     *          description="ID of the visitor card number",
     *          required=false,
     *
     *          @OA\Schema(type="string"),
     *          example="001"
     *      ),
     *
     *      @OA\Parameter(
     *          name="residence_id",
     *          in="query",
     *          description="ID of the Residence",
     *          required=false,
     *
     *          @OA\Schema(type="integer"),
     *          example=3019
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="Success",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="current_page", type="integer", example=1),
     *                  @OA\Property(property="data", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=10552),
     *                      @OA\Property(property="residence_id", type="integer", example=3019),
     *                      @OA\Property(property="visitor_card_no", type="string", example="020"),
     *                      @OA\Property(property="is_custom", type="integer", example=0),
     *                      @OA\Property(property="created_at", type="string", example="2024-02-10T13:55:48.000000Z"),
     *                      @OA\Property(property="updated_at", type="string", example="2024-02-10T13:55:48.000000Z"),
     *                      @OA\Property(property="deleted_at", type="string", example=null),
     *                  )),
     *                  @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/visitor-cards?page=1"),
     *                  @OA\Property(property="from", type="integer", example=1),
     *                  @OA\Property(property="last_page", type="integer", example=1),
     *                  @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/visitor-cards?page=1"),
     *                  @OA\Property(property="links", type="array", @OA\Items(
     *                      @OA\Property(property="url", type="string", example=null),
     *                      @OA\Property(property="label", type="string", example="Previous"),
     *                      @OA\Property(property="active", type="boolean", example=false),
     *                  )),
     *                  @OA\Property(property="next_page_url", type="string", example=null),
     *                  @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/visitor-cards"),
     *                  @OA\Property(property="per_page", type="integer", example=25),
     *                  @OA\Property(property="prev_page_url", type="string", example=null),
     *                  @OA\Property(property="to", type="integer", example=11),
     *                  @OA\Property(property="total", type="integer", example=11),
     *              ),
     *              @OA\Property(property="message", type="string", example="Success")
     *          )
     *      ),
     *
     *      @OA\Response(
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
            $response = $this->visitorCardRepository->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/v1/visitor-cards/{id}",
     *     summary="Show visitor card details",
     *     description="Get details of a specific visitor card by ID.",
     *     operationId="showVisitorCard",
     *     tags={"Visitor Cards"},
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
     *         description="ID of the visitor card",
     *
     *         @OA\Schema(type="integer", example=10006)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=10006),
     *                  @OA\Property(property="residence_id", type="integer", example=3019),
     *                  @OA\Property(property="visitor_card_no", type="string", example="001"),
     *                  @OA\Property(property="is_custom", type="integer", example=0),
     *                  @OA\Property(property="created_at", type="string", example="2023-09-20T17:51:06.000000Z"),
     *                  @OA\Property(property="updated_at", type="string", example="2023-09-20T17:51:06.000000Z"),
     *                  @OA\Property(property="deleted_at", type="string", example=null),
     *             ),
     *             @OA\Property(property="message", type="string", example="Success"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *     ),
     * )
     *
     * @param  int  $id
     * @return Response
     */
    public function show(int $id)
    {
        try {
            $response = $this->visitorCardRepository->show($id);

            return success($response);
        } catch (ModelNotFoundException $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
