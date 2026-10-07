<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class FacilityAndAmenity extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'facilities_and_amenities';

    protected $fillable = [
        'name',
        'name_in_thai',
        'type',
        'amenity_type',
        'is_active',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'icon_url',
    ];

    /**
     * Get the claimable title that owns the facility or amenity.
     *
     * @return HasMany
     */
    public function claimableTitleFacilityAndAmenities()
    {
        return $this->hasMany(ClaimableTitleFacilityAndAmenity::class);
    }

    /**
     * The claimable titles associated with this facility or amenity.
     *
     * @return BelongsToMany
     */
    public function claimableTitles()
    {
        return $this->belongsToMany(
            ClaimableTitle::class,
            'claimable_title_facility_and_amenity',
            'facility_and_amenity_id',
            'claimable_title_id'
        )->withTimestamps();
    }

    /**
     * Interact with the amenity icon url.
     *
     * @return Attribute
     */
    protected function iconUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia();

                return count($mediaItems) > 0
                    ? $mediaItems[0]->getFullUrl()
                    : 'https://dashboard.mymooban.co.th/images/no-image.png';
            }
        );
    }
}
