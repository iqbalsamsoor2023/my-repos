<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use NotificationChannels\Fcm\FcmChannel;

class SupportTicketCommentPosted extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title_th;

    protected $body_th;

    protected $content;

    protected $supportTicketComment;

    protected $supportTicket;

    protected $receiver;

    protected $sender;

    protected $role;

    protected $pm_role = 'Property Management';

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    public $localizedTitleKey;

    public $titleParams;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($supportTicketComment, SupportTicket $supportTicket, User $receiver, User $sender)
    {
        $this->onQueue('NotificationQueue');

        $this->role = $receiver->roles;

        // The receiver may have no roles assigned, so never index into the collection.
        $isPropertyManagement = $receiver->hasRole($this->pm_role);

        if ($isPropertyManagement) {
            $this->huaweiTokenType = 'PmTalk';
            $this->huaweiAppId = config('huawei.huawei-app-id.pmocPMTalk');
            $this->huaweiClientSecret = config('huawei.huawei-client-secret.pmocPMTalk');
        } else {
            $this->huaweiTokenType = 'Mymooban';
            $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
            $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');
        }
        $this->supportTicketComment = $supportTicketComment;
        $this->supportTicket = $supportTicket;
        $this->receiver = $receiver;
        $this->sender = $sender;
        $this->content = '';

        if (App::getLocale() == 'th') {
            if ($isPropertyManagement) {
                $this->title = 'แชทข้อความใหม่จากคุณ '.$this->sender->name.', '.$this->supportTicket->unit?->unit_number;
            } else {
                $this->title = 'แชทข้อความใหม่จากนิติฯ '.$this->supportTicket->unit?->residence?->name_th;
            }

            if (isset($this->supportTicketComment['data']['content'])) {
                $this->content = Str::of($this->supportTicketComment['data']['content'])->title();
            }

            $this->body = $this->content;
        } else {
            if ($isPropertyManagement) {
                $this->title = 'New message from '.$this->sender->name.', '.$this->supportTicket->unit?->unit_number;
            } else {
                $this->title = 'New message from Juristic '.$this->supportTicket->unit?->residence?->name;
            }
            if (isset($this->supportTicketComment['data']['content'])) {
                $this->content = Str::of($this->supportTicketComment['data']['content'])->title();
            }

            $this->body = $this->content;
        }

        $this->localizedTitleKey = 'notification.pm_support_ticket_comment_created.title';
        $this->titleParams = [
            'residence_en' => $this->supportTicket->unit?->residence?->name,
            'residence_th' => $this->supportTicket->unit?->residence?->name_th,
            'unit' => $this->supportTicket->unit?->unit_number,
        ];
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
            // $huaweiDevices = $notifiable->devices()
            //     ->whereNotNull('huawei_token')
            //     ->pluck('huawei_token')
            //     ->toArray();
            // if (count($huaweiDevices) > 0) {
            //     $channels[] = HuaweiChannel::class;
            // }
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
            'model_id' => $this->supportTicketComment['data']['id'],
            'message_title' => $this->title,
            'message_body' => $this->body,
            'unit_id' => $this->supportTicket->unit_id,
            'localized_title_key' => $this->localizedTitleKey,
            'title_params' => $this->titleParams,
        ];
    }
}
