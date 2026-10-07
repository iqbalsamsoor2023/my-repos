<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class SupportTicketCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $support_ticket;

    protected $role;

    protected $pm_role = 'Property Management';

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(SupportTicket $support_ticket)
    {
        $this->onQueue('NotificationQueue');

        $this->role = isset($support_ticket->assigned_to) ? $support_ticket->assignedTo->roles : '-';

        if ($this->role[0]->name == $this->pm_role) {
            $this->huaweiTokenType = 'PmTalk';
            $this->huaweiAppId = config('huawei.huawei-app-id.pmocPMTalk');
            $this->huaweiClientSecret = config('huawei.huawei-client-secret.pmocPMTalk');
        } else {
            $this->huaweiTokenType = 'Mymooban';
            $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
            $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');
        }

        $this->support_ticket = $support_ticket;

        $user = User::findOrFail($support_ticket->submitted_by);

        $this->role = $user->roles;

        if ($this->role[0]->name == $this->pm_role) {
            if (App::getLocale() == 'th') {
                $this->title = 'ตั๋วสนับสนุนใหม่';
                $this->body = $this->support_ticket->case_generated_no.' ถูกสร้างขึ้น';
            } else {
                $this->title = 'New Support Ticket';
                $this->body = $this->support_ticket->case_generated_no.' has been created';
            }
        } else {
            if (App::getLocale() == 'th') {
                $this->title = 'ตั๋วสนับสนุนใหม่';
                $this->body = $this->support_ticket->case_generated_no.' ถูกสร้างขึ้น';
            } else {
                $this->title = 'New Support Ticket';
                $this->body = $this->support_ticket->case_generated_no.' has been created';
            }
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
            'model_type' => SupportTicket::class,
            'model_id' => $this->support_ticket->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'unit_id' => $this->support_ticket->unit_id,
        ];
    }
}
