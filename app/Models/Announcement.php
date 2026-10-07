<?php

namespace App\Models;

use App\Enums\UserReaction\ReactionTypeEnum;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Announcement extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'id',
        'residence_id',
        'title',
        'description',
        'is_active',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
        'document_url',
    ];

    /**
     * Get the residence that owns the Announcement.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the user that create the Announcement.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user that updated the Announcement.
     *
     * @return BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the model role record for the announcement.
     *
     * @return BelongsTo
     */
    public function modelHasRole()
    {
        return $this->belongsTo(ModelHasRole::class, 'created_by', 'model_id');
    }

    /**
     * Get the unit-specific targets for the announcement.
     *
     * @return HasMany
     */
    public function announcementUnits()
    {
        return $this->hasMany(AnnouncementUnit::class);
    }

    /**
     * The units this announcement is targeted to.
     *
     * @return BelongsToMany
     */
    public function units()
    {
        return $this->belongsToMany(Unit::class, 'announcement_unit', 'announcement_id', 'unit_id');
    }

    /**
     * Get all annoucement's user reactions.
     *
     * @return MorphMany
     */
    public function userReactions()
    {
        return $this->morphMany(UserReaction::class, 'reactable');
    }

    /**
     * Interact with the announcement's images url.
     *
     * @return Attribute
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('images');

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
     * Interact with the announcement's document url.
     *
     * @return Attribute
     */
    protected function documentUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('document');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }

    /**
     * Scope to include reaction counts
     */
    public function scopeWithReactionCounts($query)
    {
        return $query->withCount([
            'userReactions as user_like_count' => function ($query) {
                $query->where('reaction_type', (string) ReactionTypeEnum::LIKE->value);
            },
            'userReactions as user_read_count' => function ($query) {
                $query->where('reaction_type', (string) ReactionTypeEnum::READ->value);
            }
        ]);
    }
}
