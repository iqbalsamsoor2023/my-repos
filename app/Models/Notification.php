<?php

namespace App\Models;

use App\Traits\LocalizableNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Notification extends BaseModel
{
    use LocalizableNotification;

    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    public $incrementing = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Interact with the data
     *
     * @param  string  $value
     * @return Attribute
     */
    public function data(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $notificationData = json_decode($value, true);
                $notificationData = $this->getLocalizedNotificationData($notificationData);
                unset($notificationData['localized_title_key']);
                unset($notificationData['localized_body_key']);
                unset($notificationData['title_params']);
                unset($notificationData['body_params']);

                return $notificationData;
            },
        );
    }
}
