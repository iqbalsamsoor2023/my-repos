<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Models\Maintenance;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\Unit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class MaintenanceUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $type;

    protected $maintenance;

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    public $localizedTitleKey;

    public $localizedBodyKey;

    public $bodyParams;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Maintenance $maintenance)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'Mymooban';
        $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');

        $this->maintenance = $maintenance;

        $publicClaimTypes = [
            'App\\Models\\ResidenceAmenity',
            'App\\Models\\ResidenceAmenityOption',
        ];

        if (in_array($this->maintenance->maintainable_type, $publicClaimTypes)) {
            $type = 'Public';
            $title = 'Public Maintenance report from '.$maintenance->reportedBy->name;
            $title_th = 'การแจ้งซ่อมส่วนกลางจาก '.$maintenance->reportedBy->name;

            if ($maintenance->maintainable_type == 'App\\Models\\ResidenceAmenity') {
                $item = ResidenceAmenity::whereId($maintenance->maintainable_id)->first()?->facilityAndAmenity;
                $itemName = $item?->name ?? 'N/A';
                $itemNameTh = $item?->name_in_thai ?? 'N/A';
            } elseif ($maintenance->maintainable_type == 'App\\Models\\ResidenceAmenityOption') {
                $item = ResidenceAmenityOption::whereId($maintenance->maintainable_id)->first();
                $itemName = $item?->name ?? 'N/A';
                $itemNameTh = $item?->name_in_thai ?? 'N/A';
            }

            $message = $itemName.' status has been updated to '.MaintenanceStatus::from($maintenance->status)->label();
            $message_th = $itemNameTh.' ได้รับการอัปเดทสถานะเป็น '.MaintenanceStatus::from($maintenance->status)->label();

            $this->localizedTitleKey = 'notification.public_maintenance_status_updated.title';
            $this->localizedBodyKey = 'notification.public_maintenance_status_updated.body';
            $this->bodyParams = [
                'item' => $itemName,
                'status' => $maintenance->status,
            ];
        } elseif ($this->maintenance->maintainable_type == 'App\Models\Unit') {
            $type = 'Private';
            $title = 'Private Maintenance report status update';
            $title_th = 'การแจ้งซ่อมส่วนตัวของคุณได้รับการอัปเดตสถานะ';
            $unit = Unit::whereId($maintenance->maintainable_id)->first();

            $message = 'Residence unit '.$unit->unit_number.' status has been updated to '.MaintenanceStatus::from($maintenance->status)->label();
            $message_th = 'บ้านเลขที่ '.$unit->unit_number.' ได้รับการอัปเดตสถานะเป็น '.MaintenanceStatus::from($maintenance->status)->label();

            $this->localizedTitleKey = 'notification.private_maintenance_status_updated.title';
            $this->localizedBodyKey = 'notification.private_maintenance_status_updated.body';
            $this->bodyParams = [
                'unit' => $unit->unit_number,
                'status' => $maintenance->status,
            ];
        }

        if (App::getLocale() == 'th') {
            $this->title = $title_th;
            $this->body = $message_th;
            $this->type = $type;
        } else {
            $this->title = $title;
            $this->body = $message;
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
            'localized_title_key' => $this->localizedTitleKey,
            'localized_body_key' => $this->localizedBodyKey,
            'body_params' => $this->bodyParams,
        ];
    }
}
