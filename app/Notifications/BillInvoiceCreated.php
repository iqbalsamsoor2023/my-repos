<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use NotificationChannels\Fcm\FcmChannel;

class BillInvoiceCreated extends Notification implements ShouldQueue
{
    use Queueable;

    private $invoice;

    protected $due_date;

    protected $bill_month;

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
    public function __construct(Invoice $invoice)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'Mymooban';
        $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');

        $this->invoice = $invoice;

        $this->due_date = Carbon::parse($this->invoice->due_date)->format('d-m-Y');
        $this->bill_month = Carbon::parse($this->invoice->bill_date)->format('M Y');

        if (App::getLocale() == 'th') {
            $this->title = 'บิลค่าใช้จ่ายเลขที่ '.$this->invoice->invoice_no.' พร้อมให้ตรวจสอบแล้ว';
            $this->body = 'วันครบกำหนดชำระคือ '.$this->due_date;
        } else {
            $this->title = Str::title('Your bill with Invoice Number ').$this->invoice->invoice_no.(Str::title(' for '.$this->bill_month.' is ready!'));
            $this->body = 'Your payment due date is '.$this->due_date;
        }

        $this->localizedTitleKey = 'notification.bill_created.title';
        $this->titleParams = [
            'invoice' => $this->invoice->invoice_no,
        ];
        $this->localizedBodyKey = 'notification.bill_created.body';
        $this->bodyParams = [
            'date' => $this->due_date,
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
            'model_id' => $this->invoice->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'unit_id' => $this->invoice->payer_unit_id,
            'localized_title_key' => $this->localizedTitleKey,
            'title_params' => $this->titleParams,
            'localized_body_key' => $this->localizedBodyKey,
            'body_params' => $this->bodyParams,
        ];
    }
}
