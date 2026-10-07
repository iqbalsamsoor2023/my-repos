<?php

namespace App\Models;

use App\Enums\UserReaction\ReactionTypeEnum;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Event extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'id',
        'residence_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'is_active',
        'is_cancel',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'image_url',
        'rsvp_status',
        'read_status',
    ];

    /**
     * Get the residence that owns the Event.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the rvsps that owns the Event.
     *
     * @return HasMany
     */
    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
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
     * Get all event's user reactions.
     *
     * @return MorphMany
     */
    public function userReactions()
    {
        return $this->morphMany(UserReaction::class, 'reactable');
    }

    /**
     * Interact with the event residence id.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function residenceId(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value,
        );
    }

    /**
     * Interact with the event title.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function title(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value,
        );
    }

    /**
     * Interact with the event description.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function description(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value,
        );
    }

    /**
     * Interact with the event start at.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function startAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'),
            set: fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'),
        );
    }

    /**
     * Interact with the event end at.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function endAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'),
            set: fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'),
        );
    }

    /**
     * Interact with the event active status.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function isActive(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value,
        );
    }

    /**
     * Interact with the event cancel status.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function isCancel(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value,
        );
    }

    /**
     * Interact with the event created by.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function createdBy(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value,
        );
    }

    /**
     * Interact with the event created by.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function updatedBy(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value,
        );
    }

    /**
     * Accessor to get the event duration.
     *
     * @return Attribute
     */
    protected function getEventDurationAttribute(): string
    {
        $start_date = new DateTime($this->start_at);
        $end_date = new DateTime($this->end_at);
        $interval = $start_date->diff($end_date);
        $duration = $interval->format('%a');
        $days = ($duration > 1) ? __('Days') : __('Day');
        $event_duration = $duration.' '.$days;

        return $event_duration;
    }

    /**
     * Interact with the event's images url.
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
     * Interact with the event rvsp status.
     *
     * @param  string  $value
     * @return Attribute
     */
    protected function rsvpStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $rsvp_status = null;

                if (request()->user_id) {
                    if (isset($this->rsvps)) {
                        foreach ($this->rsvps as $rsvp) {
                            if (request()->user_id == $rsvp->user_id) {
                                $rsvp_status = (int) $rsvp->is_going;
                                break; // Exit the loop once we find the user
                            }
                        }
                    }
                }

                return $rsvp_status; // Return the rvsp status
            }
        );
    }

    /**
     * Interact with the event read status.
     *
     * @param  string  $value
     * @return Attribute
     */
    protected function readStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $read_at = null;

                if (request()->user_id) {
                    $notification = Notification::where('type', '!=', 'Filament\Notifications\DatabaseNotification')
                        ->whereJsonContains('data->model_type', 'App\Models\Event')
                        ->whereJsonContains('data->model_id', $this->id)
                        ->where('notifiable_id', request()->user_id)
                        ->first();

                    if ($notification) {
                        $read_at = is_null($notification->read_at) ? 0 : 1;
                    }
                }

                return $read_at; // Return the rvsp status
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
