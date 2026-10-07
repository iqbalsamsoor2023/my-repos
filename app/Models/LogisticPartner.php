<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LogisticPartner extends BaseModel implements HasMedia
{
    use Filterable, InteractsWithMedia, SoftDeletes;

    protected $table = 'logistic_partners';

    protected $fillable = [
        'category',
        'most_usage',
        'modes',
        'name',
    ];

    /**
     * Get the parcel for the Courier.
     *
     * @return HasMany
     */
    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class);
    }

    /**
     * Interact with the courier's logo url.
     *
     * @return Attribute
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia();
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }
}
