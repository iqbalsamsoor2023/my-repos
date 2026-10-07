<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmenityTimeslot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'amenity_timeslotable_id',
        'amenity_timeslotable_type',
        'quota',
        'day',
        'start_at',
        'end_at',
        'is_active',
    ];

    /**
     * Get the parent timeslots model (residence amenity or residence amenity option).
     *
     * @return MorphTo
     */
    public function amenityTimeslotable(): MorphTo
    {
        return $this->morphTo();
    }
}
