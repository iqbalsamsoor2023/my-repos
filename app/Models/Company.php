<?php

namespace App\Models;

use App\Events\CompanyCreated;
use App\Models\Erp\ThailandProvince;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Company extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'id',
        'old_id',
        'province_id',
        'user_id',
        'type',
        'name',
        'name_th',
        'contact_email',
        'contact_number',
        'address',
        'person_in_charges',
        'website_url',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'person_in_charges' => 'array',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => CompanyCreated::class,
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
    ];

    /**
     * Get the province that owns the Company.
     *
     * @return BelongsTo
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(ThailandProvince::class);
    }

    /**
     * Get the user of the Company.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the residences in the Company.
     *
     * @return HasMany
     */
    public function residences(): HasMany
    {
        return $this->hasMany(Residence::class, 'property_management_id');
    }

    /**
     * Get the subscription expires in the Company.
     *
     * @return HasMany
     */
    public function subscriptionExpires(): HasMany
    {
        return $this->hasMany(SubscriptionExpire::class);
    }

    /**
     * Get the companies name in english and thai.
     *
     * @return Attribute
     */
    protected function nameInThai(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['name_th'].' ('.$this->attributes['name'].')',
        );
    }

    /**
     * Interact with the company's image url.
     *
     * @return Attribute
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('company_logo');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }
}
