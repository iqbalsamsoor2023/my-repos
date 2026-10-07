<?php

namespace App\Actions\Comment;

use App\Models\Comment;

class GetCommentAction
{
    public function execute($request)
    {
        $comment = Comment::with(
            'user',
            'commentable',
            'commentable.maintainable',
            'commentable.maintainable.residence',
            'commentable.maintainable.residence.subdistrict.district',
            'commentable.maintainable.residence.subdistrict.district.province'
        );

        if (isset($request->id)) {
            $comment = $comment->whereId($request->id);
        }

        if (isset($request->commentable_type)) {
            $comment = $comment->where('commentable_type', $request->commentable_type);
        }

        if (isset($request->commentable_id)) {
            $comment = $comment->where('commentable_id', $request->commentable_id);
        }

        if (isset($request->has_pagination) && ($request->has_pagination == false)) {
            return $comment->orderBy('created_at', 'DESC')->get();
        }

        return $comment->orderBy('id', 'DESC')->paginate(20);
    }
}
