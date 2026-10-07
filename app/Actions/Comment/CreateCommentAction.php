<?php

namespace App\Actions\Comment;

use App\Exceptions\GeneralException;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;

class CreateCommentAction
{
    public function execute(StoreCommentRequest $request)
    {
        $comment = Comment::create($request->only([
            'user_id',
            'commentable_type',
            'commentable_id',
            'content',
        ]));

        if (! $comment) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating Comment');
        }

        if ($request->hasFile('comment_image')) {
            $this->uploadCommentImage($comment);
        }

        return $comment;
    }

    private function uploadCommentImage(Comment $comment)
    {
        if ($comment->commentable_type == "App\Models\Maintenance") {
            $comment->addMediaFromRequest('comment_image')->withCustomProperties(['type' => 'maintenance_comment'])->toMediaCollection('comment_image');
        }
    }
}
