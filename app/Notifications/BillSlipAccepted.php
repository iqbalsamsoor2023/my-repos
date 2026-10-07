<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class BillSlipAccepted extends Notification implements ShouldQueue
{
    use Queueable;

    private $transaction;

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
    public function __construct(Transaction $transaction)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'Mymooban';
        $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');

        $this->transaction = $transaction;

        if (App::getLocale() == 'th') {
            $this->title = 'การชำระเงินของคุณสำหรับใบแจ้งหนี้หมายเลข '.$this->transaction->invoice->invoice_no.' ได้รับการยืนยันแล้ว';
            $this->body = 'ได้รับการชำระเงินแล้วเมื่อวันที่  '.date_format($this->transaction->created_at, 'd-m-Y H:i');
        } else {
            $this->title = 'Your payment for invoice number '.$this->transaction->invoice->invoice_no.' has been accepted';
            $this->body = 'Payment received on '.date_format($this->transaction->created_at, 'd-m-Y H:i');
        }

        $this->localizedTitleKey = 'notification.bill_accepted.title';
        $this->titleParams = [
            'invoice' => $this->transaction->invoice->invoice_no,
        ];
        $this->localizedBodyKey = 'notification.bill_accepted.body';
        $this->bodyParams = [
            'datetime' => date_format($this->transaction->created_at, 'd-m-Y H:i'),
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
            'model_type' => 'App\Models\Invoice',
            'model_id' => $this->transaction?->invoice?->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'unit_id' => $this->transaction->invoice->payer_unit_id,
            'localized_title_key' => $this->localizedTitleKey,
            'title_params' => $this->titleParams,
            'localized_body_key' => $this->localizedBodyKey,
            'body_params' => $this->bodyParams,
        ];
    }
}
