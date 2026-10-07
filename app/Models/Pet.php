<?php

namespace App\Models;

use App\Enums\Pet\PetType;
use App\Events\PetCreated;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Pet extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'id',
        'unit_id',
        'user_id',
        'breed',
        'type',
        'year',
        'generated_pet_no',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => PetCreated::class,
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_back_url',
        'image_bottom_url',
        'image_front_url',
        'image_left_url',
        'image_right_url',
        'image_top_url',
    ];

    /**
     * Get the unit that owns the Pet.
     *
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the user that owns the Pet.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user that created the Pet.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Interact with the pet type.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function type(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => PetType::tryFrom($value)?->label(),
            set: fn ($value) => $value,
        );
    }

    /**
     * Accessor to get the pet age.
     *
     * @return Attribute
     */
    protected function getPetAgeAttribute(): string
    {
        $current_year = date('Y');

        if (! empty($this->year)) {
            $age = $current_year - $this->year;

            if ($this->year < 2016) {
                $pet_age = '4 '.__('Years old');
            } else {
                $year_birth = ($age > 1) ? __('Years old') : __('Year old');
                $pet_age = $age.' '.$year_birth;
            }
        } else {
            $pet_age = '-';
        }

        return $pet_age;
    }

    /**
     * Interact with the pet's back image url.
     *
     * @return Attribute
     */
    protected function imageBackUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('back');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }

    /**
     * Interact with the pet's bottom image url.
     *
     * @return Attribute
     */
    protected function imageBottomUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('bottom');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }

    /**
     * Interact with the pet's front image url.
     *
     * @return Attribute
     */
    protected function imageFrontUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('front');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }

    /**
     * Interact with the pet's left image url.
     *
     * @return Attribute
     */
    protected function imageLeftUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('left');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }

    /**
     * Interact with the pet's right image url.
     *
     * @return Attribute
     */
    protected function imageRightUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('right');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }

    /**
     * Interact with the pet's top image url.
     *
     * @return Attribute
     */
    protected function imageTopUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('top');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }
}
