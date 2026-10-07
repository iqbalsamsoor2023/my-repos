<?php

namespace App\Notifications;

use App\Models\ResidenceAmenity;
use App\Channels\HuaweiChannel;
use App\Enums\FacilityBooking\FacilityBookingStatus;
use App\Models\AmenityBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class AmenityBookingCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title_th;

    protected $body_th;

    protected $badge;

    protected $amenityBooking;

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(AmenityBooking $amenityBooking)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'PmTalk';
        $this->huaweiAppId = config('huawei.huawei-app-id.pmocPMTalk');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.pmocPMTalk');

        $this->amenityBooking = $amenityBooking;

        $date_text = '';

        // same day
        if (date('Ymd', strtotime($this->amenityBooking->start_at)) == date('Ymd', strtotime($this->amenityBooking->end_at))) {
            $date_text = 'on '.date('d M Y (l)', strtotime($this->amenityBooking->start_at)).' from '.date('h:iA', strtotime($this->amenityBooking->start_at)).' to '.date('h:iA', strtotime($this->amenityBooking->end_at));
            $date_text_th = 'บน '.date('d M Y (l)', strtotime($this->amenityBooking->start_at)).' จาก '.date('h:iA', strtotime($this->amenityBooking->start_at)).' ไปยัง '.date('h:iA', strtotime($this->amenityBooking->end_at));
        } else {
            $date_text = 'from '.date('d M Y (l) h:iA', strtotime($this->amenityBooking->start_at)).' to '.date('d M Y (l) h:iA', strtotime($this->amenityBooking->end_at));
            $date_text_th = 'จาก '.date('d M Y (l) h:iA', strtotime($this->amenityBooking->start_at)).' ไปยัง '.date('d M Y (l) h:iA', strtotime($this->amenityBooking->end_at));
        }

        if ($this->amenityBooking->status == FacilityBookingStatus::APPROVED->value) {
            $this->title = 'Amenity Booking Approved';
            $this->title_th = 'อนุมัติ​การจอง';
        } elseif ($this->amenityBooking->status == FacilityBookingStatus::REJECT->value) {
            $this->title = 'Amenity Booking Reject';
            $this->title_th = 'การปฏิเสธการจองห้องพัก';
        } else {
            $this->title = 'Amenity Booking Request';
            $this->title_th = 'คำขอจองห้องพัก';
        }

        if (App::getLocale() == 'th') {
            $this->title = 'Amenity has been booked!';
            $this->body_th = (
                $this->amenityBooking->amenity_bookable_type === ResidenceAmenity::class
                    ? $this->amenityBooking->amenityBookable?->facilityAndAmenity?->name
                    : $this->amenityBooking->amenityBookable?->name
            ).
            ' at '.$this->amenityBooking->amenityBookable?->residence?->name.
            ' สถานที่ได้รับการจองโดย '.$this->amenityBooking->user?->name.
            ' '.$date_text_th;
        } else {
            $this->title = 'Amenity has been booked!';
            $this->body = (
                $this->amenityBooking->amenity_bookable_type === ResidenceAmenity::class
                    ? $this->amenityBooking->amenityBookable?->facilityAndAmenity?->name
                    : $this->amenityBooking->amenityBookable?->name
            ).
            ' at '.$this->amenityBooking->amenityBookable?->residence?->name.
            ' facility has been booked by '.$this->amenityBooking->user?->name.
            ' '.$date_text;
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
            'model_type' => 'App\Models\AmenityBooking',
            'model_id' => $this->amenityBooking->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'amenity_bookable_id' => $this->amenityBooking->amenity_bookable_id,
            'amenity_bookable_type' => $this->amenityBooking->amenity_bookable_type,
            'unit_id' => $this->amenityBooking->unit_id,
            'user_id' => $this->amenityBooking->user_id,
        ];
    }
}
