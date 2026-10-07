<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parking extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'residence_id',
        'type',
        'rate_mode',
        'is_discount_coupon',
        'discount_type',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the residence that owns the Parking.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the calculations in the Parking.
     *
     * @return HasMany
     */
    public function calculations(): HasMany
    {
        return $this->hasMany(Calculation::class);
    }
}
