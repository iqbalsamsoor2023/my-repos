<?php

namespace App\Notifications;

use App\Actions\Notification\CountUnreadNotificationAction;
use App\Channels\HuaweiChannel;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use NotificationChannels\Fcm\FcmChannel;

class BillSlipPaid extends Notification implements ShouldQueue
{
    use Queueable;

    private $invoice;

    private $transaction;

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice, Transaction $transaction, CountUnreadNotificationAction $countUnreadNotificationAction)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'PmTalk';
        $this->huaweiAppId = config('huawei.huawei-app-id.pmocPMTalk');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.pmocPMTalk');

        $this->invoice = $invoice;
        $this->transaction = $transaction;
        $this->count_unread_notification_action = $countUnreadNotificationAction;

        $this->title = Str::title('Payment for invoice number '.$invoice->invoice_no.' has been made.');
        $this->body = 'Pending Approval';
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
            'model_type' => 'App\Models\Transaction',
            'model_id' => $this->transaction->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'unit_id' => $this->transaction->invoice->payer_unit_id,
        ];
    }
}
