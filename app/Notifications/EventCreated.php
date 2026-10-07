<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class EventCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public $title;

    public $body;

    protected $role;

    protected $event;

    protected $badge;

    protected $developer_role = 'Developer';

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
    public function __construct(Event $event)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'Mymooban';
        $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');

        $this->event = $event;

        $user = User::findOrFail($this->event->getAttributes()['created_by']);

        $this->role = $user->roles;

        if ($this->role[0]->name == $this->developer_role) {
            if (App::getLocale() == 'th') {
                $this->title = 'แจ้งกิจกรรมจากผู้พัฒนาโครงการ ถึงผู้อยู่อาศัย';
                $this->body = $this->event->title;
            } else {
                $this->title = "Developer's New event";
                $this->body = $this->event->title;
            }
        } else {
            if (App::getLocale() == 'th') {
                $this->title = 'กิจกรรมให้คุณเข้าร่วมจากนิติฯ โครงการ';
                $this->body = $this->event->title;
            } else {
                $this->title = 'New Event For Residence';
                $this->body = $this->event->title;
            }
        }

        $this->localizedTitleKey = 'notification.event_created.title';

        $this->titleParams = [
            'residence_en' => $this->event->residence->name,
            'residence_th' => $this->event->residence->name_th,
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
        $user = User::with('roles')->find($this->event->created_by);
        $roles = $user->roles;

        if ($roles->count() > 0) {
            $role = $roles->first()->name;
        } else {
            $role = null;
        }

        return [
            'model_type' => 'App\Models\Event',
            'model_id' => $this->event->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'residence_id' => $this->event->residence_id,
            'localized_title_key' => $this->localizedTitleKey,
            'title_params' => $this->titleParams,
            'created_by' => $user->id,
            'created_by_role' => $role,
        ];
    }
}
