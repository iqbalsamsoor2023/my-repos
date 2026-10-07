<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use function Sentry\captureException;
use App\Actions\Maintenance\StoreCommentAction;
use App\Exceptions\GeneralException;
use App\Http\Requests\Maintenance\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Maintenance;
use Exception;

class CommentController extends Controller
{
    /**
     * comment the specified resource in storage.
     *
     * @param StoreCommentRequest $request
     * @param  int  $id
     * @return Response
     */
    public function store(StoreCommentRequest $request, int $id)
    {
        try {
            $maintenance = Maintenance::findOrFail($id);

            $request->merge([
                'commentable_type' => 'App\Models\Maintenance',
                'commentable_id' => $maintenance->id,
            ]);

            $comment = Comment::create($request->only([
                'user_id',
                'commentable_type',
                'commentable_id',
                'content',
            ]));

            if (! $comment) {
                return redirect()->route('filament.admin.resources.private-maintenances.index')->with('flash_error', __('Failed'));
            }

            if ($request->hasFile('comment_image')) {
                $commentAction = new StoreCommentAction;
                $commentAction->uploadCommentImage($comment, $request);
            }

            if (! $request->redirect) {
                return;
            }

            return redirect()->route('filament.admin.resources.private-maintenances.view', ['record' => $maintenance->id]);
        } catch (GeneralException $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()], $ex->getStatusCode());
        } catch (Exception $ex) {
            captureException($ex);

            return response()->json(['message' => $ex->getMessage()]);
        }
    }
}
