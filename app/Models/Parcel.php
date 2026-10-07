<?php

namespace App\Models;

use App\Enums\Parcel\PickupType;
use App\Events\ParcelCreated;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Parcel extends Model implements Auditable, HasMedia
{
    use HasFactory, InteractsWithMedia, \OwenIt\Auditing\Auditable, SoftDeletes;

    protected $fillable = [
        'id',
        'unit_id',
        'courier_id',
        'receiver_id',
        'receiver_name',
        'pickup_person_contact_no',
        'pickup_person_name',
        'qr_code',
        'parcel_generated_no',
        'tracking_no',
        'description',
        'status',
        'pickup_type',
        'pickup_time',
        'created_by_mmb_user_id',
        'updated_by_mmb_user_id',
        'created_by_sgoc_user_id',
        'updated_by_sgoc_user_id',
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
        'created' => ParcelCreated::class,
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'qr_code_url',
        'signature_image_url',
        'image_url',
        'image_urls',
        'pickup_type_name',
    ];

    /**
     * Get the unit that owns the Parcel.
     *
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the courier that owns the Parcel.
     *
     * @return BelongsTo
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(LogisticPartner::class, 'courier_id', 'id');
    }

    /**
     * Get the receiver that owns the Parcel.
     *
     * @return BelongsTo
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user that created the Parcel.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_mmb_user_id', 'id');
    }

    /**
     * Get the user that updated the Parcel.
     *
     * @return BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_mmb_user_id', 'id');
    }

    /**
     * Get the Security Guard user that created the Parcel.
     *
     * @return BelongsTo
     */
    public function createdBySG(): BelongsTo
    {
        return $this->belongsTo(SgocUser::class, 'created_by_sgoc_user_id', 'id');
    }

    /**
     * Get the Security Guard user that updated the Parcel.
     *
     * @return BelongsTo
     */
    public function updatedBySG(): BelongsTo
    {
        return $this->belongsTo(SgocUser::class, 'updated_by_sgoc_user_id', 'id');
    }

    /**
     * Interact with the parcel pickup type name.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function pickupTypeName(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->pickup_type == PickupType::RECIPIENT->value) {
                    return __(ucfirst(strtolower(PickupType::RECIPIENT->name)));
                } elseif ($this->pickup_type == PickupType::ON_BEHALF->value) {
                    return __('On-behalf');
                } else {
                    return '';
                }
            }
        );
    }

    /**
     * Interact with the parcel's images url.
     *
     * @return Attribute
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('parcel_images');

                if (! empty($mediaItems)) {
                    $urls = [];
                    foreach ($mediaItems as $mediaItem) {
                        $urls[] = $mediaItem->getFullUrl();
                    }

                    $url = collect($urls)->implode(',');
                } else {
                    $url = 'https://dashboard.mymooban.co.th/images/no-image.png';
                }

                return $url;
            }
        );
    }

    /**
     * Interact with the incident report's images url.
     */
    protected function imageUrls(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('parcel_images');

                if (! empty($mediaItems)) {
                    return $mediaItems->map(function ($mediaItem) {
                        return $mediaItem->getFullUrl();
                    })->toArray();
                }

                return ['https://dashboard.mymooban.co.th/images/no-image.png'];
            }
        );
    }

    /**
     * Interact with the parcel's receiver signature image url.
     *
     * @return Attribute
     */
    protected function signatureImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('signature_image');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }

    /**
     * Interact with the qr code's url.
     *
     * @return Attribute
     */
    public function qrCodeUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => route('parcels.qr', ['qr_code' => $this->attributes['qr_code']]),
        );
    }
}
