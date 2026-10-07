<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActivationModule\GetActivationModuleRequest;
use App\Http\Resources\ActivationModuleResource;
use App\Models\ActivationModule;
use App\Models\ResidenceFeature;
use Exception;
use Illuminate\Support\Facades\Cache;
use Log;

class ActivationModuleController extends Controller
{
    protected $activationModule;

    protected $residenceFeature;

    public function __construct(ActivationModule $activationModule, ResidenceFeature $residenceFeature)
    {
        $this->activationModule = $activationModule;
        $this->residenceFeature = $residenceFeature;
    }

    /**
     * Display a listing of the resource.
     *
     *    @OA\Get(
     *      path="/api/v1/activation-modules",
     *      operationId="getData",
     *      tags={"Activation Modules"},
     *      summary="Get data by residence ID and module",
     *      description="Retrieve data based on residence ID and module.",
     *      security={
     *         {"bearer_token": {}}
     *      },
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
     *          name="residence_id",
     *          in="query",
     *          description="ID of the residence",
     *          required=true,
     *
     *          @OA\Schema(type="integer"),
     *          example=3019
     *      ),
     *
     *      @OA\Parameter(
     *          name="module",
     *          in="query",
     *          description="Module name",
     *          required=true,
     *
     *          @OA\Schema(type="string", enum={"residence", "visitor", "visitor slip", "parking"}),
     *          example="residence"
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful response",
     *
     *          @OA\JsonContent(
     *          type="object",
     *
     *          @OA\Property(property="data", type="array",
     *
     *              @OA\Items(
     *                  type="object",
     *
     *                  @OA\Property(property="id", type="integer", example=35),
     *                  @OA\Property(property="residence_id", type="integer", example=3019),
     *                  @OA\Property(property="module", type="string", example="residence"),
     *                  @OA\Property(property="module_type", type="integer", example=1),
     *                  @OA\Property(property="module_type_name", type="string", example="Inbox"),
     *                  @OA\Property(property="is_active", type="integer", example=1),
     *                  @OA\Property(property="created_at", type="string", format="date-time", example="06-12-2023 03:15:00 PM"),
     *                  @OA\Property(property="updated_at", type="string", format="date-time", example="06-12-2023 03:15:00 PM"),
     *              )
     *          ),
     *          @OA\Property(property="message", type="string", example="Success")
     *          ),
     *      ),
     *
     *      @OA\Response(
     *           response=422,
     *           description="Validation error response",
     *       )
     *      ),
     *
     * @param GetActivationModuleRequest $request
     * @return Response
     */
    public function index(GetActivationModuleRequest $request)
    {
        try {
            // Create a clean cache key
            $cacheKey = 'activation_modules:'
                . $request->residence_id . ':'
                . $request->module;

            // Use cache tags with remember
            $activationModules = Cache::tags(['activation_modules'])
                ->remember($cacheKey, 3600, function () use ($request) {

                    if ($request->module === 'residence') {
                        return ResidenceFeature::filter($request->all())->get();
                    }

                    return ActivationModule::filter($request->all())->get();
                });

            return ActivationModuleResource::collection($activationModules);
        } catch (GeneralException $ex) {
            captureException($ex);
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
