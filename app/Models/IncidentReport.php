<?php

namespace App\Models;

use App\Models\Sgoc\ReportCategoryItem;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class IncidentReport extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $connection = 'sgoc';

    protected $appends = [
        'image_url',
    ];

    public function unit()
    {
        return $this->setConnection('mysql')
            ->belongsTo(Unit::class, 'mmb_unit_id');
    }

    public function residence()
    {
        return $this->setConnection('mysql')
            ->belongsTo(Residence::class, 'mmb_residence_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(SgocUser::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(SgocUser::class, 'updated_by');
    }

    public function reportCategoryItem()
    {
        return $this->belongsTo(ReportCategoryItem::class);
    }

    /**
     * Get the mediable model.
     *
     * @return MorphMany
     */
    public function media(): MorphMany
    {
        return $this->morphMany(SgocMedia::class, 'model');
    }

    /**
     * Interact with the incident report's images url.
     */
    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia();
                $mediaItem = $mediaItems->first();

                if ($mediaItem) {
                    $url = $mediaItem->getFullUrl();
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
    public function imagesUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia();

                if (! $mediaItems->isEmpty()) {
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
}
