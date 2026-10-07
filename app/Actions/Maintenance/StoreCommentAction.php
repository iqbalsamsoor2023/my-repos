<?php

namespace App\Actions\Maintenance;

use Filament\Actions\Action;
use App\Enums\User\RoleType;
use App\Exceptions\GeneralException;
use App\Filament\Resources\PrivateMaintenances\PrivateMaintenanceResource;
use App\Filament\Resources\PublicMaintenances\PublicMaintenanceResource;
use App\Http\Requests\Maintenance\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Maintenance;
use App\Models\Residence;
use App\Models\Unit;
use App\Models\UnitUser;
use App\Models\User;
use App\Notifications\MaintenanceCommentPosted;
use Filament\Notifications\Notification as FilamentsNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;

class StoreCommentAction
{
    public function execute(StoreCommentRequest $request, Maintenance $maintenance)
    {
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
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating comment');
        }

        $this->uploadCommentImage($comment, $request);
        $this->sendCommentNotification($maintenance, $comment);

        return $comment;
    }

    public function uploadCommentImage(Comment $comment, $request)
    {
        if ($request->hasFile('comment_image')) {
            $comment->addMediaFromRequest('comment_image')->withCustomProperties(['type' => 'maintenance_comment'])->toMediaCollection('comment_image');
        }
    }

    public function sendCommentNotification($maintenance, $comment)
    {
        $user = User::whereId($comment->user_id)->firstOrFail();

        if (! $user) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'User Not Exists!');
        }

        if ($user->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::PROPERTY_MANAGEMENT->value])) {
            // notify to all residents in the unit
            $unitUsers = UnitUser::where('unit_id', $maintenance->maintainable_id)->get();

            foreach ($unitUsers as $unitUser) {
                Notification::send($unitUser->user, new MaintenanceCommentPosted($maintenance, $comment));
            }
        } elseif ($user->hasAnyRole([RoleType::COMMUNITY_COMMITTEE->value, RoleType::UNIT_OWNER->value, RoleType::UNIT_TENANT->value])) {
            // notify Property Management (PM)
            $residence = Residence::whereId($maintenance->maintainable->residence_id)->first();
            $recipient = User::findOrFail($residence->property_management_user_id);
            $recipient->notify(new MaintenanceCommentPosted($maintenance, $comment));

            $maintenance = Maintenance::whereId($maintenance->id)->first();

            if ($maintenance->maintainable_type === Unit::class) {
                $notificationTitle = 'You got a new comment on a maintenance report at '
                    .$residence->name.' ('.$maintenance->maintainable->unit_number.')!';
                $notificationTitleTh = 'คุณได้รับความคิดเห็นใหม่จากรายงานการบำรุงรักษาที่ '
                    .$residence->name_th.' ('.$maintenance->maintainable->unit_number.')!';

                $resourceClass = PrivateMaintenanceResource::class;
            } else {
                $notificationTitle = 'You got a new comment on a maintenance report at '
                    .$residence->name.'!';
                $notificationTitleTh = 'คุณได้รับความคิดเห็นใหม่จากรายงานการบำรุงรักษาที่ '
                    .$residence->name_th.'!';

                $resourceClass = PublicMaintenanceResource::class;
            }

            $notificationTitleLocalized = App::getLocale() === 'th' ? $notificationTitleTh : $notificationTitle;

            $url = $resourceClass::getUrl('view', ['record' => $maintenance->id]);

            // send notification to PM dashboard
            FilamentsNotification::make()
                ->title($notificationTitleLocalized)
                ->actions([
                    Action::make('view')
                        ->label(__('View Comment'))
                        ->url($url)
                        ->button()
                        ->openUrlInNewTab(true),
                ])
                ->sendToDatabase($recipient);
        } else {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Unauthorized Access!');
        }
    }
}
