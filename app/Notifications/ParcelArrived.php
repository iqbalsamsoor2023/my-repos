<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\Parcel;
use App\Models\Sgoc\User as SgocUser;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class ParcelArrived extends Notification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public $title;

    public $body;

    protected $title_th;

    protected $body_th;

    protected $parcel;

    protected $pickup_msg;

    protected $pickup_msg_th;

    protected $role;

    protected $residence;

    protected $badge;

    protected $sc_role = 'Security Guard';

    protected $rc_role = 'Receptionist';

    protected $pm_role = 'Property Management';

    protected $pickup_sc = 'Please pick up at Guard House';

    protected $pickup_rc = 'Please pick up at Reception';

    protected $pickup_pm = 'Please pick up at PM Office';

    protected $pickup_sc_th = 'กรุณารับที่ ป้อม รปภ.';

    protected $pickup_rc_th = 'กรุณารับที่แผนกต้อนรับ';

    protected $pickup_pm_th = 'ติดต่อรับได้ที่สำนักงานนิติบุคคล';

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
    public function __construct(Parcel $parcel)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'Mymooban';
        $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');

        $this->parcel = $parcel;

        if (is_null($parcel->created_by_mmb_user_id) == false) {
            $user = User::whereId($this->parcel->created_by_mmb_user_id)->first();

            $this->role = $user->roles;
            $this->role = $this->role[0]->name;
        } else {
            $sgocUser = SgocUser::whereId($this->parcel->created_by_sgoc_user_id)->first();

            $this->role = $sgocUser->roles;
            $this->role = $sgocUser->roles[0]->name;
        }

        $this->pickup_msg = $this->pickup($this->role);
        $this->pickup_msg_th = $this->pickup_th($this->role);
        $this->residence = isset($this->parcel->unit->residence) ? $this->parcel->unit->residence->name : '';

        if (App::getLocale() == 'th') {
            $this->title = 'พัสดุใหม่ของคุณมาถึงแล้ว';
            $this->body = 'พัสดุสำหรับ '.$parcel->receiver_name.' ที่ '.$this->parcel->unit->residence->name_th.' มาถึงแล้ว กรุณา. '.$this->pickup_msg_th;
        } else {
            $this->title = 'A new parcel has arrived';
            $this->body = 'Parcel for '.$parcel->receiver_name.' at '.$this->parcel->unit->residence->name.'has arrived. '.$this->pickup_msg;
        }

        $this->localizedTitleKey = 'notification.parcel_created.title';
        $this->localizedBodyKey = 'notification.parcel_created.body';
        $this->bodyParams = [
            'user' => $parcel->receiver_name,
            'residence_en' => $this->residence,
            'residence_th' => $this->parcel->unit->residence ? $this->parcel->unit->residence->name_th : $this->residence,
            'remark_en' => $this->pickup_msg,
            'remark_th' => $this->pickup_msg_th,
        ];
    }

    protected function pickup($check_role)
    {
        if ($check_role == $this->sc_role) {
            return $this->pickup_sc;
        } elseif ($check_role == $this->rc_role) {
            return $this->pickup_rc;
        } elseif ($check_role == $this->pm_role) {
            return $this->pickup_pm;
        }
    }

    protected function pickup_th($check_role)
    {
        if ($check_role == $this->sc_role) {
            return $this->pickup_sc_th;
        } elseif ($check_role == $this->rc_role) {
            return $this->pickup_rc_th;
        } elseif ($check_role == $this->pm_role) {
            return $this->pickup_pm_th;
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
            'model_type' => 'App\Models\Parcel',
            'model_id' => $this->parcel->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'unit_id' => $this->parcel->unit_id,
            'localized_title_key' => $this->localizedTitleKey,
            'localized_body_key' => $this->localizedBodyKey,
            'body_params' => $this->bodyParams,
        ];
    }
}
