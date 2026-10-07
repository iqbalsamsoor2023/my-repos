<?php

namespace App\Models;

use App\Enums\Residence\Features;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ResidenceFeature extends Model
{
    use Filterable, HasFactory;

    protected $table = 'residence_feature';

    protected $fillable = [
        'residence_id',
        'feature_id',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the residence that owns the ResidenceFeature.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the feature that owns the ResidenceFeature.
     *
     * @return BelongsTo
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    /**
     * Scope a query to only include active residence features.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query): Builder
    {
        return $query->where('is_active', 1);
    }

    /**
     * Interact with the features model name.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function featureName(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->feature_id == Features::INBOX->value) {
                    return __(ucfirst(strtolower(Features::INBOX->name)));
                } elseif ($this->feature_id == Features::VISITOR->value) {
                    return __(ucfirst(strtolower(Features::VISITOR->name)));
                } elseif ($this->feature_id == Features::CLAIM->value) {
                    return __(ucfirst(strtolower(Features::CLAIM->name)));
                } elseif ($this->feature_id == Features::PARCEL->value) {
                    return __(ucfirst(strtolower(Features::PARCEL->name)));
                } elseif ($this->feature_id == Features::BOOKING->value) {
                    return __(ucfirst(strtolower(Features::BOOKING->name)));
                } elseif ($this->feature_id == Features::DEVELOPER->value) {
                    return __(ucfirst(strtolower(Features::DEVELOPER->name)));
                } elseif ($this->feature_id == Features::CONTACT->value) {
                    return __(ucfirst(strtolower(Features::CONTACT->name)));
                } elseif ($this->feature_id == Features::BILLING->value) {
                    return __(ucfirst(strtolower(Features::BILLING->name)));
                } elseif ($this->feature_id == Features::PARKING_FEE_BASIC->value) {
                    return __(Str::headline(ucfirst(strtolower(Features::PARKING_FEE_BASIC->name))));
                } elseif ($this->feature_id == Features::PARKING_FEE_PRO->value) {
                    return __(Str::headline(ucfirst(strtolower(Features::PARKING_FEE_PRO->name))));
                } else {
                    return '';
                }
            }
        );
    }
}
