<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Enums\Maintenance\MaintenanceVerificationStatus;
use App\Models\Maintenance;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\Unit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class MaintenanceVerificationUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $type;

    protected $maintenance;

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    protected $model;

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

        if ($this->maintenance->is_verified == MaintenanceVerificationStatus::NOT_VERIFIED->label()) {
            $this->model = 'unverified';
        } elseif ($this->maintenance->is_verified == MaintenanceVerificationStatus::VERIFIED->label()) {
            $this->model = 'verified';
        } else {
            $this->model = 'pending for verification';
        }

        $publicClaimTypes = [
            'App\\Models\\ResidenceAmenity',
            'App\\Models\\ResidenceAmenityOption',
        ];

        if (in_array($this->maintenance->maintainable_type, $publicClaimTypes)) {
            $type = 'Public';

            if ($maintenance->maintainable_type == 'App\\Models\\ResidenceAmenity') {
                $item = ResidenceAmenity::whereId($maintenance->maintainable_id)->first()?->facilityAndAmenity;
                $itemName = $item?->name ?? 'N/A';
                $itemNameTh = $item?->name_in_thai ?? 'N/A';
            } elseif ($maintenance->maintainable_type == 'App\\Models\\ResidenceAmenityOption') {
                $item = ResidenceAmenityOption::whereId($maintenance->maintainable_id)->first();
                $itemName = $item?->name ?? 'N/A';
                $itemNameTh = $item?->name_in_thai ?? 'N/A';
            }

            $message = $itemName.' status has been updated to '.$this->model;
            $message_th = $itemNameTh.' ได้รับการอัปเดทสถานะเป็น '.$this->model;
        } else {
            $type = 'Private';
            $unit = Unit::whereId($maintenance->maintainable_id)->first();

            $message = 'Residence unit '.$unit->unit_number.' status has been updated to '.$this->model;
            $message_th = 'หน่วยที่พักอาศัย '.$unit->unit_number.' สถานะได้รับการอัปเดตแล้ว '.$this->model;
        }

        if (App::getLocale() == 'th') {
            $this->title = 'อัพเดทสถานะการแจ้งซ่อม';
            $this->body = $message_th;
            $this->type = $type;
        } else {
            $this->title = 'Maintenance report status update';
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
        ];
    }
}
