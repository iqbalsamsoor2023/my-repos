<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimableTitleFacilityAndAmenity extends BaseModel
{
    use HasFactory;

    protected $table = 'claimable_title_facility_and_amenity';

    protected $fillable = [
        'claimable_title_id',
        'facility_and_amenity_id',
    ];

    /**
     * Get the claimableTitle that owns the AmenityComponent.
     *
     * @return BelongsTo
     */
    public function claimableTitle(): BelongsTo
    {
        return $this->belongsTo(ClaimableTitle::class);
    }

    /**
     * Get the facilityAndAmenity that owns the AmenityComponent.
     *
     * @return BelongsTo
     */
    public function facilityAndAmenity(): BelongsTo
    {
        return $this->belongsTo(FacilityAndAmenity::class);
    }
}
