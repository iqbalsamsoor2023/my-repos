<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MaintenanceProgression extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'maintenance_id',
        'progress_description',
        'created_at',
        'updated_at',
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
     * Get the maintenance that owns the MaintenanceProgression.
     *
     * @return BelongsTo
     */
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }

    /**
     * Interact with the maintenance progression images url.
     *
     * @return Attribute
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('maintenance_progression_image');

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
}
