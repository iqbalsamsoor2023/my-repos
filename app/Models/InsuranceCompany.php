<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class InsuranceCompany extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'name_th',
        'type',
        'website_url',
        'is_active',
    ];

    /**
     * Interact with the company's image url.
     *
     * @return Attribute
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('insurance_company_images');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }

    /**
     * Get all of the residences for the InsuranceCompany
     *
     * @return HasMany
     */
    public function residences(): HasMany
    {
        return $this->hasMany(Residence::class);
    }

    /**
     * Get all of the users for the InsuranceCompany
     *
     * @return HasMany
     */
    public function unitUsers(): HasMany
    {
        return $this->hasMany(UnitUser::class);
    }
}
