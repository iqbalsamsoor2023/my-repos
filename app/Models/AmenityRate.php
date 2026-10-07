<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmenityRate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'amenity_rateable_id',
        'amenity_rateable_type',
        'price_per_hour',
        'price_per_day',
    ];

    /**
     * Get the parent amenityRateable model (residence amenity or residence amenity option).
     *
     * @return MorphTo
     */
    public function amenityRateable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getPricePerHourAttribute($value)
    {
        return $this->formatDecimal($value);
    }

    public function getPricePerDayAttribute($value)
    {
        return $this->formatDecimal($value);
    }

    protected function formatDecimal($value)
    {
        return (float) $value == (int) $value ? (int) $value : (float) $value;
    }
}
