<?php

namespace App\Models;

use App\Events\AmenityBookingCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmenityBooking extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'amenity_bookable_id',
        'amenity_bookable_type',
        'user_id',
        'unit_id',
        'ref_no',
        'start_at',
        'end_at',
        'status',
        'created_by',
        'updated_by',
        'created_at', // will remove after successfully migrate
        'updated_at', // will remove after successfully migrate
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => AmenityBookingCreated::class,
    ];

    /**
     * Get the parent amenities model (residence amenity or residence amenity option).
     *
     * @return MorphTo
     */
    public function amenityBookable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that owns the AmenityBooking.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the unit that owns the AmenityBooking.
     *
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the user that owns the AmenityBooking.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Get the user that owns the AmenityBooking.
     *
     * @return BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
