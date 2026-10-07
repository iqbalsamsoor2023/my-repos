<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SupportTicketComment extends BaseModel implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $connection = 'mmbcnerp';

    protected $table = 'comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parent_id',
        'user_id',
        'commentable_type',
        'commentable_id',
        'content',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'comment_url',
    ];

    /**
     * Get the user that writes the Comment.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->setConnection('mysql')->belongsTo(User::class);
    }

    /**
     * Get the parent commentable model (maintenance).
     *
     * @return MorphTo
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the mediable model.
     *
     * @return MorphMany
     */
    public function media(): MorphMany
    {
        return $this->morphMany(MmbcnErpMedia::class, 'model');
    }

    /**
     * Interact with the cover image url.
     *
     * @param  string  $value
     */
    public function commentUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('comment_image');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : null;

                return $url;
            }
        );
    }

    /**
     * Update a media collection by deleting and inserting again with new values.
     * This method overrides parent media model class defined in config and change it back.
     *
     * @param  array  $collectionName
     * @return Collection
     */
    public function updateMedia(array $newMediaArray, string $collectionName = 'default'): Collection
    {
        $mediaClass = config('media-library.media_model');

        config(['media-library.media_model' => MmbcnErpMedia::class]);

        $media = parent::updateMedia($newMediaArray, $collectionName);

        config(['media-library.media_model' => $mediaClass]);

        return $media;
    }

    /**
     * Get the class name for polymorphic relations.
     * This used for media class to fill model_type based on mmbcn erp model.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return Comment::class;
    }
}
