<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResidenceAmenity extends Pivot
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'residence_id',
        'facility_and_amenity_id',
        'is_active',
        'is_claimable',
        'is_bookable',
    ];

    protected static function booted()
    {
        static::deleting(function ($residenceAmenity) {
            $residenceAmenity?->residenceAmenityOptions()->delete();
        });
    }

    /**
     * Get the Residence that owns the FacilityAndAmenity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the FacilityAndAmenity that owns the ResidenceAmenity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function facilityAndAmenity(): BelongsTo
    {
        return $this->belongsTo(FacilityAndAmenity::class);
    }

    /**
     * Get the residence amenity options for the Amenity
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function residenceAmenityOptions(): HasMany
    {
        return $this->hasMany(ResidenceAmenityOption::class, 'residence_amenity_id');
    }

    /**
     * Get the amenity rate for the Amenity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function amenityRate(): MorphOne
    {
        return $this->morphOne(AmenityRate::class, 'amenity_rateable');
    }

    /**
     * Get the timeslots that owns by the Amenity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function amenityTimeslots(): MorphMany
    {
        return $this->morphMany(AmenityTimeslot::class, 'amenity_timeslotable');
    }

    /**
     * Get the amenity bookings that owns by the Amenity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function amenityBookings(): MorphMany
    {
        return $this->morphMany(AmenityBooking::class, 'amenity_bookable');
    }

    /**
     * Get the maintenances that owns by the Amenity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function maintenances(): MorphMany
    {
        return $this->morphMany(Maintenance::class, 'maintainable');
    }
}
