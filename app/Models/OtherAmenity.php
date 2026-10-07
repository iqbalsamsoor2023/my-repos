<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class OtherAmenity extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'residence_id',
        'is_other_amenity',
        'remark',
        'is_show_warranty_reminder',
        'remind_day',
        'created_at',
        'updated_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'warranty_handbook_url',
    ];

    /**
     * Get the residence that own the OtherAmenity.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the amenities that own the OtherAmenity.
     *
     * @return HasMany
     */
    public function amenities(): HasMany
    {
        return $this->hasMany(Amenity::class, 'residence_id', 'residence_id');
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
