<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserReaction\UserReactionRequest;
use App\Repositories\UserReactionRepository;
use stdClass;

class UserReactionController extends Controller
{
    protected $userReactionRepository;

    public function __construct(UserReactionRepository $userReactionRepository)
    {
        $this->userReactionRepository = $userReactionRepository;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/user-reactions",
     *     summary="Store user reactions",
     *     description="Endpoint to store user reaction.",
     *     tags={"User Reaction"},
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
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="reactable_id", type="integer"),
     *                 @OA\Property(property="reactable_type", type="integer", description="1 for Announcement, 2 for event"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="reaction_type", type="integer", description="0 for removing reaction, 1 for like, 2 for read"),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="201",
     *         description="Success"
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
     * @param UserReactionRequest $request
     * @return Response
     */
    public function store(UserReactionRequest $request)
    {
        $this->userReactionRepository->store($request);

        return success(new stdClass);
    }
}
