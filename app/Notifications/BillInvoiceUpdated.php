<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use NotificationChannels\Fcm\FcmChannel;

class BillInvoiceUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    private $invoice;

    protected $due_date;

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

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

        $this->title = Str::title('Please check your bill with Invoice Number ').$this->invoice->invoice_no.(Str::title(' has been updated'));
        $this->body = 'Your payment due date is '.$this->due_date;
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
        ];
    }
}
