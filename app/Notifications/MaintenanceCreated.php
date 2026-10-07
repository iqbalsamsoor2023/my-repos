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

class MaintenanceCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public $title;

    public $body;

    protected $type;

    protected $unit_id;

    protected $maintenance;

    protected $badge;

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Maintenance $maintenance)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'PmTalk';
        $this->huaweiAppId = config('huawei.huawei-app-id.pmocPMTalk');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.pmocPMTalk');

        $this->maintenance = $maintenance;

        if (in_array($this->maintenance->maintainable_type, [
            'App\\Models\\ResidenceAmenity',
            'App\\Models\\ResidenceAmenityOption',
        ])) {
            $type = 'Public';

            $unit_ids = Unit::where('residence_id', $this->maintenance->maintainable->residence_id)->pluck('id');
            $this->unit_id = $unit_ids;
        } else {
            $type = 'Private';
            $this->unit_id = $this->maintenance->maintainable_id;
        }

        if (App::getLocale() == 'th') {
            $this->title = 'รายงานการบำรุงรักษาจาก {name}';
            $this->body = Str::limit($this->maintenance->issue_description, 255);
            $this->type = $type;
        } else {
            $this->title = 'Maintenance report from {name}';
            $this->body = Str::limit($this->maintenance->issue_description, 255);
            $this->type = $type;
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
        $name = $this->maintenance->reported_by == $notifiable->user_id ? 'You' : $this->maintenance->unit->unit_number ?? $this->maintenance->reportedBy->name;
        $this->title = Str::replace('{name}', $name, $this->title);

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
            'model_type' => 'App\Models\Maintenance',
            'model_id' => $this->maintenance->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'type' => $this->type,
            'unit_id' => $this->unit_id,
        ];
    }
}
