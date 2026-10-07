<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VisitorParking extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'visitor_log_id',
        'calculation_id',
        'discount_value',
        'amount_to_pay',
        'amount_paid',
        'is_penalty',
        'is_stamp',
        'calculation_records',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'calculation_records' => AsCollection::class,
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'voucher_url',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('voucher_image')->singleFile();
    }

    /**
     * Get the visitor log that owns the VisitorParking.
     *
     * @return BelongsTo
     */
    public function visitorLog(): BelongsTo
    {
        return $this->belongsTo(VisitorLog::class);
    }

    /**
     * Get the calculation that owns the VisitorParking.
     *
     * @return BelongsTo
     */
    public function calculation(): BelongsTo
    {
        return $this->belongsTo(Calculation::class);
    }

    /**
     * Interact with the support ticket's file url.
     *
     * @return Attribute
     */
    protected function voucherUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('voucher_image');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/image/no-image.png';

                return $url;
            }
        );
    }
}
