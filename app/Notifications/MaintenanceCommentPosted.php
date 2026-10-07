<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\Maintenance;
use App\Models\Unit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use NotificationChannels\Fcm\FcmChannel;
use RyanChandler\Comments\Models\Comment;

class MaintenanceCommentPosted extends Notification implements ShouldQueue
{
    use Queueable;

    public $title;

    public $body;

    protected $role;

    protected $title_th;

    protected $body_th;

    protected $maintenance;

    protected $comment;

    protected $pm_role = 'Property Management';

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Maintenance $maintenance, Comment $comment)
    {
        $this->onQueue('NotificationQueue');

        $this->role = isset($comment->user_id) ? $comment->user->roles : '-';

        if ($this->role[0]->name == $this->pm_role) {
            $this->huaweiTokenType = 'PmTalk';
            $this->huaweiAppId = config('huawei.huawei-app-id.pmocPMTalk');
            $this->huaweiClientSecret = config('huawei.huawei-client-secret.pmocPMTalk');
        } else {
            $this->huaweiTokenType = 'Mymooban';
            $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
            $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');
        }

        $this->maintenance = $maintenance;
        $this->comment = $comment;

        $maintenance = Maintenance::whereId($this->comment->commentable_id)->firstOrFail();
        $unit = Unit::whereId($maintenance->maintainable_id)->firstOrFail();

        if (App::getLocale() == 'th') {
            $this->title = 'คุณได้รับความคิดเห็นใหม่จากรายงานการบำรุงรักษาที่'.$unit->residence->name_th.'!';
            $this->body = isset($maintenance->amenity) ? Str::of($maintenance->amenity['amenity_name'])->title() : null;
        } else {
            $this->title = 'You got a new comment from the maintenance report at '.$unit->residence->name.'!';
            $this->body = isset($maintenance->amenity) ? Str::of($maintenance->amenity['amenity_name'])->title() : null;
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $channels = [];
        if (isset($notifiable) && $notifiable->devices()->count() > 0) {
            $huaweiDevices = $notifiable->devices()
                ->whereNotNull('huawei_token')
                ->pluck('huawei_token')
                ->toArray();
            if (count($huaweiDevices) > 0) {
                $channels[] = HuaweiChannel::class;
            }
            $channels[] = FcmChannel::class;
            $channels[] = 'database';
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'model_type' => 'App\Models\Comment',
            'model_id' => $this->comment->id,
            'message_title' => $this->title,
            'message_body' => $this->body ?? null,
            'unit_id' => $this->maintenance->maintainable_id,
        ];
    }
}
