<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RyanChandler\Comments\Contracts\IsComment;
use RyanChandler\Comments\Models\Comment as CommentPackage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Comment extends CommentPackage implements HasMedia, IsComment
{
    use InteractsWithMedia, SoftDeletes;

    protected $guarded = [];

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
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
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
     * Get the user that writes the Comment.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * Get the parent that writes the Comment.
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * Get the childrens in the Comment.
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * Interact with the comment's image url.
     *
     * @return Attribute
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('comment_image');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : null;

                return $url;
            }
        );
    }
}
