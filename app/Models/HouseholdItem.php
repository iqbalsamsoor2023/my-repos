<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\HouseholdItem\ItemCategoryEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class HouseholdItem extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = ['category', 'name', 'name_th', 'is_active'];

    /**
     * Scope a query to only include living space.
     */
    public function scopeLivingSpace(Builder $query): void
    {
        $query->where('category', ItemCategoryEnum::SPACE->value);
    }

    /**
     * Scope a query to only include furniture.
     */
    public function scopeFurniture(Builder $query): void
    {
        $query->where('category', ItemCategoryEnum::FURNITURE->value);
    }

    /**
     * Scope a query to only include home appliance.
     */
    public function scopeHomeAppliance(Builder $query): void
    {
        $query->where('category', ItemCategoryEnum::HOME_APPLIANCE->value);
    }

    /**
     * Get the home appliances that owns the Unit.
     *
     * @return HasMany
     */
    public function units()
    {
        return $this->belongsToMany(Unit::class, 'unit_household_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
