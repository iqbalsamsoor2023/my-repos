<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\HealthQuestionnaire\HealthQuestionnaireCollection;
use App\Services\HealthQuestionnaireService;
use Exception;
use Illuminate\Http\Request;

class HealthQuestionnaireController extends Controller
{
    protected $service;

    public function __construct(HealthQuestionnaireService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/health-questionnaires",
     *     summary="Get Health Questionnaires",
     *     description="Retrieve health questionnaires",
     *     tags={"Health Questionnaires"},
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
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="question", type="string", example="Do you smoke?"),
     *                 @OA\Property(property="question_th", type="string", example="คุณสูบบุหรี่ หรือไม่"),
     *                 @OA\Property(property="is_active", type="integer", example=1),
     *                 @OA\Property(property="health_questionnaire_answers", type="array",
     *
     *                      @OA\Items(type="object",
     *
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="answer", type="string", example="No, Skip"),
     *                          @OA\Property(property="answer_th", type="string", example="ไม่, ข้ามไป"),
     *                      )),
     *                  )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/health-questionnaires?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/health-questionnaires?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/health-questionnaires"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=7),
     *             @OA\Property(property="total", type="integer", example=7),
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
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return success(new HealthQuestionnaireCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
