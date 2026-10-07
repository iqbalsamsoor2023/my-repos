<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class WarrantySetting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'residence_id',
        'has_other_option',
        'remark',
        'has_warranty_reminder',
        'reminder_day',
        'has_appointment_schedule',
        'has_verification'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'warranty_handbook_url',
    ];


    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Interact with the warranty handbook url.
     *
     * @return Attribute
     */
    protected function warrantyHandbookUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('document');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }
}
