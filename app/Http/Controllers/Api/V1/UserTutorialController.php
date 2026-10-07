<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Enums\ResourceMaterial\ResourceTypeEnum;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ResourceMaterial\ResourceMaterialCollection;
use App\Services\ResourceMaterialService;
use Exception;
use Illuminate\Http\Request;

class UserTutorialController extends Controller
{
    protected $service;

    public function __construct(ResourceMaterialService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/user-tutorials",
     *     summary="Get User Tutorials",
     *     description="Retrieve user tutorials",
     *     tags={"User Tutorials"},
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
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *           @OA\Property(property="data", type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=46),
     *                 @OA\Property(property="platform", type="string", example="mmb"),
     *                 @OA\Property(property="module", type="string", example="Residence"),
     *                 @OA\Property(property="title", type="string", example="1.2 การจัดการข้อมูลลูกบ้าน (Manage Residents)"),
     *                 @OA\Property(property="type", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2024-03-28 00:37:18"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2024-03-28 00:37:18"),
     *                 @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                 @OA\Property(property="tutorial_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="media", type="array",
     *
     *                    @OA\Items(
     *                    )
     *                 ),
     *             )),
     *
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/user-tutorials?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=2),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/user-tutorials?page=2"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/user-tutorials?page=2"),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/user-tutorials"),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=20),
     *             @OA\Property(property="total", type="integer", example=39),
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
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            $request->merge([
                'type' => ResourceTypeEnum::USER_TUTORIAL->value,
            ]);
            $response = $this->service->index($request);

            return success(new ResourceMaterialCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
