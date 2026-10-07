<?php

namespace App\Services;

use App\Actions\Comment\CreateCommentAction;
use App\Actions\Comment\GetCommentAction;
use App\Http\Requests\Comment\StoreCommentRequest;
use Illuminate\Http\Request;

class CommentService
{
    public function index(Request $request)
    {
        $getCommentAction = new GetCommentAction;
        $comments = $getCommentAction->execute($request);

        return $comments;
    }

    public function create(StoreCommentRequest $request)
    {
        $commentAction = new CreateCommentAction;
        $comment = $commentAction->execute($request);

        return $comment;
    }
}
