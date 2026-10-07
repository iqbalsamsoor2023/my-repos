<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VehicleBrand extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    private const FALLBACK_IMAGE_URL = 'https://dashboard.mymooban.co.th/images/no-image.png';

    protected $fillable = [
        'name',
        'name_th',
        'gpl_priority',
    ];

    protected $appends = [
        'image_url',
    ];

    /**
     * Get the country that owns the VehicleBrand
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the vehicle model that owns the Brand.
     */
    public function vehicleModels(): HasMany
    {
        return $this->hasMany(VehicleModel::class);
    }

    /**
     * Get the vehicle model that owns the Brand.
     */
    public function vehicleModelVehicles(): HasManyThrough
    {
        return $this->hasManyThrough(Vehicle::class, VehicleModel::class, 'vehicle_brand_id', 'vehicle_model_id', 'id', 'id');
    }

    /**
     * Interact with the vehicle image url.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->firstMediaUrlOrFallback('vehicle_brand_images')
        );
    }

    private function firstMediaUrlOrFallback(string $collection): string
    {
        return $this->getFirstMediaUrl($collection) ?: self::FALLBACK_IMAGE_URL;
    }
}
