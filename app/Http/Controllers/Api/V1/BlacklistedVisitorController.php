<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Repositories\BlacklistedVisitorRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BlacklistedVisitorController extends Controller
{
    protected $blacklistedVisitorRepository;

    public function __construct(BlacklistedVisitorRepository $blacklistedVisitorRepository)
    {
        $this->blacklistedVisitorRepository = $blacklistedVisitorRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/blacklisted-visitors",
     *     summary="Get Blacklisted Visitors",
     *     description="Retrieve blacklisted visitors based on residence ID",
     *     tags={"Blacklisted Visitors"},
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
     *         name="residence_id",
     *         in="query",
     *         description="ID of the residence",
     *         required=false,
     *
     *         @OA\Schema(type="integer"),
     *         example=3019
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
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="visitor_id", type="integer", example=86928),
     *                 @OA\Property(property="residence_id", type="integer", example=3019),
     *                 @OA\Property(property="blacklist_remark", type="string", example="testing"),
     *                 @OA\Property(property="vehicle_plate_no", type="string", example="VI4467"),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2023-09-21T07:55:23.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2023-09-21T07:55:23.000000Z"),
     *                 @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                 @OA\Property(property="image_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="photo_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                 @OA\Property(property="visitor", type="object",
     *                     @OA\Property(property="id", type="integer", example=86928),
     *                     @OA\Property(property="name", type="string", example="Daniel"),
     *                     @OA\Property(property="contact_no", type="string", example=null),
     *                     @OA\Property(property="id_type", type="integer", example=1),
     *                     @OA\Property(property="id_number", type="string", example="1235677656324"),
     *                     @OA\Property(property="created_at", type="string", format="datetime", example="2023-09-21T07:55:23.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime", example="2023-09-21T07:55:23.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                 ),
     *                 @OA\Property(property="media", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=154904),
     *                     @OA\Property(property="model_type", type="string", example="App\\Models\\BlacklistedVisitor"),
     *                     @OA\Property(property="model_id", type="integer", example=12),
     *                     @OA\Property(property="uuid", type="string", example="5a9c4b5a-065d-4b1e-8cc7-23frf324"),
     *                     @OA\Property(property="collection_name", type="string", example="blacklist_visitor"),
     *                     @OA\Property(property="name", type="string", example="886_1"),
     *                     @OA\Property(property="file_name", type="string", example="jTVnOi7gNFioGiYfK1tb8g11X1JmV3-metaODg2XzEuanBn-.jpg"),
     *                     @OA\Property(property="mime_type", type="string", example="image/jpeg"),
     *                     @OA\Property(property="disk", type="string", example="cos"),
     *                     @OA\Property(property="conversions_disk", type="string", example="cos"),
     *                     @OA\Property(property="size", type="integer", example=117885),
     *                     @OA\Property(property="manipulations", type="string", example="[]"),
     *                     @OA\Property(property="custom_properties", type="string", example="[]"),
     *                     @OA\Property(property="generated_conversions", type="string", example="[]"),
     *                     @OA\Property(property="responsive_images", type="string", example="[]"),
     *                     @OA\Property(property="order_column", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="datetime", example="2023-09-21T07:55:23.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime", example="2023-09-21T07:55:23.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="datetime", example=null),
     *                     @OA\Property(property="original_url", type="string", example="https://dashboard.mymooban.co.th/images/no-image.png"),
     *                     @OA\Property(property="preview_url", type="string", example=""),
     *                  ),
     *                ),
     *             )),
     *             @OA\Property(property="first_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/blacklisted-visitors?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="https://dashboard.mymooban.co.th/api/v1/blacklisted-visitors?page=1"),
     *             @OA\Property(property="links", type="array", @OA\Items(
     *                 @OA\Property(property="url", type="string", example=null),
     *                 @OA\Property(property="label", type="string", example="Previous"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *             )),
     *             @OA\Property(property="next_page_url", type="string", example=null),
     *             @OA\Property(property="path", type="string", example="https://dashboard.mymooban.co.th/api/v1/blacklisted-visitors"),
     *             @OA\Property(property="per_page", type="integer", example=25),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=1),
     *             @OA\Property(property="total", type="integer", example=1),
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
    public function index(Request $request): Response
    {
        try {
            $response = $this->blacklistedVisitorRepository->index($request);

            return success($response);
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
