<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VisitorSetting extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'residence_id',
        'is_qr_active',
        'created_at',
        'updated_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'pdpa_url',
    ];

    /**
     * Get the residence that owns the VisitorSetting.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Interact with the visitor setting's PDPA url.
     *
     * @return Attribute
     */
    protected function pdpaUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->media->where('collection_name', 'document');
    
                $url = $mediaItems->isNotEmpty()
                    ? $mediaItems->first()->getFullUrl()
                    : 'https://dashboard.mymooban.co.th/images/no-image.png';
    
                return $url;
            }
        );
    }
}
