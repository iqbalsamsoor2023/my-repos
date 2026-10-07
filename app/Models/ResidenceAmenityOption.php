<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResidenceAmenityOption extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'residence_amenity_id',
        'name',
        'name_in_thai',
        'is_active',
    ];

    /**
     * Get the Residence that owns the ResidenceAmenity.
     *
     * @return BelongsTo
     */
    public function residenceAmenity(): BelongsTo
    {
        return $this->belongsTo(ResidenceAmenity::class, 'residence_amenity_id');
    }

    /**
     * Get the amenity rate for the Amenity Options.
     *
     * @return MorphOne
     */
    public function amenityRate(): MorphOne
    {
        return $this->morphOne(AmenityRate::class, 'amenity_rateable');
    }

    /**
     * Get the timeslots that owns by the Amenity Options.
     *
     * @return MorphMany
     */
    public function amenityTimeslots(): MorphMany
    {
        return $this->morphMany(AmenityTimeslot::class, 'amenity_timeslotable');
    }

    /**
     * Get the amenity bookings that owns by the Amenity Options.
     *
     * @return MorphMany
     */
    public function amenityBookings(): MorphMany
    {
        return $this->morphMany(AmenityBooking::class, 'amenity_bookable');
    }

    /**
     * Get the maintenances that owns by the Amenity Options.
     *
     * @return MorphMany
     */
    public function maintenances(): MorphMany
    {
        return $this->morphMany(Maintenance::class, 'maintainable');
    }
}
