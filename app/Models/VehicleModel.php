<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleModel extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_brand_id',
        'name',
        'type',
        'body_type',
        'price_min',
        'price_max',
        'launched_year',
    ];

    /**
     * Get the brand that owns the VehicleModel.
     */
    public function vehicleBrand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class);
    }

    /**
     * Get all of the vehicles for the VehicleModel
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    protected function priceRange(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (! $this->price_min || ! $this->price_max) {
                    return '-';
                }

                return number_format($this->price_min).' - '.number_format($this->price_max);
            }
        );
    }
}
