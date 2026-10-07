<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Resources\Comment\CommentCollection;
use App\Http\Resources\Comment\CommentResource;
use App\Models\Comment;
use App\Services\CommentService;
use Exception;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    protected $service;

    public function __construct(CommentService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     *  @OA\Get(
     *     path="/api/v1/comments",
     *     summary="Get Comments",
     *     description="Retrieve information about a specific comments",
     *     operationId="getComment",
     *     tags={"Comments"},
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
     *         required=false,
     *         description="ID of the comment",
     *
     *         @OA\Schema(type="integer", example=13)
     *     ),
     *
     *     @OA\Parameter(
     *         name="commentable_id",
     *         in="query",
     *         required=false,
     *         description="ID of the commentable entity associated with the comment",
     *
     *         @OA\Schema(type="integer", example=190)
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
     *         response=200,
     *         description="Success",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found",
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            $response = $this->service->index($request);

            return success(new CommentCollection($response));
        } catch (GeneralException $ex) {
            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/comments",
     *     summary="Create a comment",
     *     description="Create a new comment",
     *     operationId="createComment",
     *     tags={"Comments"},
     *         security={
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
     *         description="Comment data",
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="user_id", type="integer", example=4772),
     *                 @OA\Property(property="commentable_type", type="string", example="App\\Models\\Maintenance"),
     *                 @OA\Property(property="commentable_id", type="integer", example=563),
     *                 @OA\Property(property="content", type="string", example="This is a comment"),
     *                 @OA\Property(property="comment_image", type="string", format="binary")
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="user_id", type="integer", example=4772),
     *                 @OA\Property(property="commentable_type", type="string", example="App\\Models\\Maintenance"),
     *                 @OA\Property(property="commentable_id", type="integer", example=563),
     *                 @OA\Property(property="content", type="string", example="This is a comment"),
     *                 @OA\Property(property="comment_image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Comment created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *              @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Comment created successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *     ),
     *     @OA\Response(response="422", description="Validation error"),
     * )
     *
     * @param StoreCommentRequest $request
     * @return Response
     */
    public function store(StoreCommentRequest $request)
    {
        try {
            $comment = $this->service->create($request);

            return success($comment, 'Comment successfully send');
        } catch (Exception $exception) {
            captureException($exception);

            return $exception;
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/v1/comments/{id}",
     *     summary="Get a specific comment",
     *     description="Retrieve details of a comment by its ID",
     *     operationId="getCommentById",
     *     tags={"Comments"},
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
     *         description="ID of the comment",
     *
     *         @OA\Schema(type="integer", example=190)
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
     *         description="Comment not found"
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
            $response = Comment::with(
                'user',
                'commentable',
                'commentable.maintainable',
                'commentable.maintainable.residence',
                'commentable.maintainable.residence.subdistrict.district',
                'commentable.maintainable.residence.subdistrict.district.province'
            )->findOrFail($id);

            return success(new CommentResource($response));
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
