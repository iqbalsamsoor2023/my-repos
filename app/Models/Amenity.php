<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Amenity extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'residence_id',
        'amenity_name',
        'warranty_period',
        'period_type',
        'supplier',
        'is_out_warranty',
        'remark',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'has_warranty',
    ];

    /**
     * Get the residence that owns the Amenity.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the other amenity that owns by Amenity.
     *
     * @return BelongsTo
     */
    public function otherAmenity(): HasOne
    {
        return $this->hasOne(OtherAmenity::class, 'residence_id', 'residence_id');
    }

    /**
     * Get the warranty reminder for the amenity
     *
     * @return HasMany
     */
    public function warrantyReminders(): HasMany
    {
        return $this->hasMany(WarrantyReminder::class);
    }

    /**
     * Interact with the announcement's document url.
     *
     * @return Attribute
     */
    protected function hasWarranty(): Attribute
    {
        return Attribute::make(
            get: function () {
                return isset($this->period_type) ? 1 : 0;
            }
        );
    }
}
