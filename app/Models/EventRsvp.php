<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventRsvp extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'event_id',
        'user_id',
        'is_going',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the event that owns the EventRsvp.
     *
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user that owns the EventRsvp.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Interact with the event rsvp created at date.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'),
            set: fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'),
        );
    }

    /**
     * Interact with the event rsvp updated at date.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'),
            set: fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'),
        );
    }
}
