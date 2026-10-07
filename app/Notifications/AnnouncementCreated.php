<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\Announcement;
use App\Models\AnnouncementUnit;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use NotificationChannels\Fcm\FcmChannel;

class AnnouncementCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $role;

    protected $announcement;

    protected $unitUser;

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
    public function __construct(Announcement $announcement, $unitUser)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'Mymooban';
        $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');

        $this->announcement = $announcement;
        $this->unitUser = $unitUser;
        $this->role = isset($announcement->createdBy) ? $announcement->createdBy->roles : '-';

        if ($this->role[0]->name == $this->developer_role) {
            if (App::getLocale() == 'th') {
                $this->title = 'ประกาศจากผู้พัฒนาโครงการ ถึงผู้อยู่อาศัย '.Str::title(isset($this->announcement->residence) ? $this->announcement->residence->name_th : '-');
            } else {
                $this->title = "Developer's Announcement For Residence ".Str::title(isset($this->announcement->residence) ? $this->announcement->residence->name : '-');
            }

            $this->localizedTitleKey = 'notification.developer_announcement_created.title';
        } else {
            if (App::getLocale() == 'th') {
                $this->title = 'ประกาศจากโครงการ '.Str::title(isset($this->announcement->residence) ? $this->announcement->residence->name_th : '-');
            } else {
                $this->title = 'Announcement For Residence '.Str::title(isset($announcement->residence) ? $announcement->residence->name : '-');
            }

            $this->localizedTitleKey = 'notification.pm_announcement_created.title';
        }

        $this->titleParams = [
            'residence_en' => isset($this->announcement->residence) ? $this->announcement->residence->name : '-',
            'residence_th' => isset($this->announcement->residence) ? $this->announcement->residence->name_th : '-',
        ];

        $this->body = Str::title(Str::limit($this->announcement->description, 1000));
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
        if (isset($notifiable)) {
            // Get devices associated with the notifiable User model
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
        $user = User::with('roles')->find($this->announcement->created_by);
        $roles = $user->roles;

        if ($roles->count() > 0) {
            $role = $roles->first()->name;
        } else {
            $role = null;
        }

        $data = [
            'model_type' => 'App\Models\Announcement',
            'model_id' => $this->announcement->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'localized_title_key' => $this->localizedTitleKey,
            'title_params' => $this->titleParams,
            'created_by' => $user->id,
            'created_by_role' => $role,
        ];

        if (AnnouncementUnit::where('announcement_id', $this->announcement->id)->count() > 0) {
            $data = array_merge($data, [
                'unit_id' => $this->unitUser->unit_id,
            ]);
        } else {
            $data = array_merge($data, [
                'residence_id' => $this->announcement->residence_id,
            ]);
        }

        return $data;
    }
}
