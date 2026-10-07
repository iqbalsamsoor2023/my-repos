<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\VisitingArrangement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class VisitorArrived extends Notification implements ShouldQueue
{
    use Queueable;

    public $title;

    public $body;

    protected $visitor;

    protected $title_th;

    protected $body_th;

    protected $model;

    protected $badge;

    protected $visiting_arrangement;

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    public $localizedTitleKey;

    public $titleParams;

    public $localizedBodyKey;

    public $bodyParams;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(VisitingArrangement $visiting_arrangement)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'Mymooban';
        $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');

        $this->visiting_arrangement = $visiting_arrangement;

        $residence_name = $visiting_arrangement->unit->residence->name;
        $unit_no = $visiting_arrangement->unit->unit_number;
        $visitor_name = $visiting_arrangement->visitorLog->visitor->name;

        if (App::getLocale() == 'th') {
            $this->title = 'คุณมีผู้มาติดต่อ '.$residence_name.', '.$unit_no;
            $this->body = 'ผู้มาติดต่อคุณชื่อ '.$visitor_name.' มาถึงแล้ว. กรุณายืนยันผู้มาติดต่อของคุณ '.$residence_name.'.';
        } else {
            $this->title = 'You got a visitor for '.$residence_name.', '.$unit_no;
            $this->body = 'Your visitor '.$visitor_name.' has arrived. Please confirm the visitor for '.$residence_name.'.';
        }

        $residenceParams = [
            'residence_en' => $residence_name,
            'residence_th' => $visiting_arrangement->unit->residence->name_th ?? $residence_name,
        ];

        $this->localizedTitleKey = 'notification.visitor_created.title';
        $this->titleParams = [
            ...$residenceParams,
            'unit' => $unit_no,
        ];
        $this->localizedBodyKey = 'notification.visitor_created.body';
        $this->bodyParams = [
            ...$residenceParams,
            'visitor' => $visitor_name,
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
        return [
            'model_type' => 'App\Models\VisitorLog',
            'model_id' => $this->visiting_arrangement->visitorLog->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'residence_id' => $this->visiting_arrangement->residence_id,
            'unit_id' => $this->visiting_arrangement->unit_id ?? null,
            'localized_title_key' => $this->localizedTitleKey,
            'title_params' => $this->titleParams,
            'localized_body_key' => $this->localizedBodyKey,
            'body_params' => $this->bodyParams,
        ];
    }
}
