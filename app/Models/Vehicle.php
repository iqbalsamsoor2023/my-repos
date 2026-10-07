<?php

namespace App\Models;

use App\Events\VehicleCreated;
use App\Models\Erp\ThailandProvince;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Vehicle extends BaseModel implements HasMedia
{
    use Filterable, HasFactory, InteractsWithMedia, SoftDeletes;

    private const FALLBACK_IMAGE_URL = 'https://dashboard.mymooban.co.th/images/no-image.png';

    protected $fillable = [
        'id',
        'province_id',
        'unit_id',
        'user_id',
        'vehicle_model_id',
        'insurance_company_id',
        'fuel_type',
        'plate_number',
        'generated_vehicle_no',
        'purchase_year', // for old versions compatibility
        'model_year',
        'roadtax_expiry_date',
        'insurance_expiry_date',
        'policy_no',
        'is_access_card',
        'is_car_sticker',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => VehicleCreated::class,
    ];

    protected $appends = [
        'image_url',
        'lpr_image_url',
        'front_image_url',
        'right_side_image_url',
        'left_side_image_url',
        'back_image_url',
        'image_roadtax_url',
    ];

    protected function casts(): array
    {
        return [
            'is_access_card' => 'boolean',
            'is_car_sticker' => 'boolean',
            'model_year' => 'integer',
            'purchase_year' => 'integer',
            'roadtax_expiry_date' => 'date',
            'insurance_expiry_date' => 'date',
        ];
    }

    /**
     * Get the province that owns the Vehicle.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(ThailandProvince::class);
    }

    /**
     * Get the unit that owns the Vehicle.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the user that owns the Vehicle.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vehicle model that owns the Vehicle.
     */
    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    /**
     * Get the insurance company that owns the Vehicle.
     */
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * Accessor to get the Vehicle age.
     */
    protected function vehicleAge(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (empty($this->model_year)) {
                    return '-';
                }

                $age = now()->year - (int) $this->model_year;
                $yearLabel = $age > 1 ? 'Years' : 'Year';

                return $age.' '.$yearLabel;
            }
        );
    }

    /**
     * Interact with the vehicle image url.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->firstMediaUrlOrFallback('vehicle_image')
        );
    }

    /**
     * Interact with the vehicle front image url.
     */
    protected function frontImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->firstMediaUrlOrFallback('vehicle_front_image')
        );
    }

    /**
     * Interact with the vehicle back image url.
     */
    protected function backImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->firstMediaUrlOrFallback('vehicle_back_image')
        );
    }

    /**
     * Interact with the vehicle right image url.
     */
    protected function rightSideImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->firstMediaUrlOrFallback('vehicle_right_image')
        );
    }

    /**
     * Interact with the vehicle left image url.
     */
    protected function leftSideImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->firstMediaUrlOrFallback('vehicle_left_image')
        );
    }

    /**
     * Interact with the vehicle LPR image url.
     */
    protected function lprImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->firstMediaUrlOrFallback('vehicle_lpr_image')
        );
    }

    /**
     * Interact with the vehicle's roadtax image url.
     */
    protected function imageRoadtaxUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->firstMediaUrlOrFallback('roadtax_image')
        );
    }

    private function firstMediaUrlOrFallback(string $collection): string
    {
        return $this->getFirstMediaUrl($collection) ?: self::FALLBACK_IMAGE_URL;
    }
}
