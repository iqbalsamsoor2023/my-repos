<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BlacklistedVisitor extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'blacklisted_visitors';

    protected $appends = [
        'image_url',
        'photo_url',
    ];

    protected $fillable = [
        'visitor_id',
        'residence_id',
        'blacklist_remark',
        'vehicle_plate_no',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the visitor that owns the BlacklistedVisitor.
     *
     * @return BelongsTo
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    /**
     * Get the residence that owns the BlacklistedVisitor.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Interact with the visitor blacklist image url.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('blacklist_visitor');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }

        );
    }

    /**
     * Interact with the visitor blacklist image url for visitor blacklist gp(wesley)
     *
     * @param  string  $value
     * @return Attribute
     */
    public function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('blacklist_visitor');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }

        );
    }
}
