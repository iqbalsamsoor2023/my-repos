<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class BillInvoiceCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invoice;

    protected $due_date;

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    public $localizedTitleKey;

    public $titleParams;

    public $localizedBodyKey;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'Mymooban';
        $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');

        $this->invoice = $invoice;

        $this->due_date = Carbon::parse($this->invoice->due_date)->format('d-m-Y');

        if (App::getLocale() == 'th') {
            $this->title = 'บิลเลขที่ '.$this->invoice->invoice_no.' ถูกยกเลิกโดยนิติฯ';
            $this->body = 'คุณไม่จำเป็นต้องดำเนินการใดๆเพิ่มเติม';
        } else {
            $this->title = 'Bill Cancelled '.$this->invoice->invoice_no.' by the Property Management.';
            $this->body = 'You don’t need to pay the cancelled bill';
        }

        $this->localizedTitleKey = 'notification.bill_cancelled.title';
        $this->titleParams = [
            'invoice' => $this->invoice->invoice_no,
        ];
        $this->localizedBodyKey = 'notification.bill_cancelled.body';
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
            'model_id' => $this->invoice->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'unit_id' => $this->invoice->payer_unit_id,
            'localized_title_key' => $this->localizedTitleKey,
            'title_params' => $this->titleParams,
            'localized_body_key' => $this->localizedBodyKey,
        ];
    }
}
