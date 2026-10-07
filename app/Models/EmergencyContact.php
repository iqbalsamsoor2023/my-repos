<?php

namespace App\Models;

use App\Enums\EmergencyContact\CoverageMode;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EmergencyContact extends BaseModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array int, string>
     */
    protected $fillable = [
        'id',
        'department_type',
        'name',
        'contact_no',
        'coverage_mode',
        'is_active',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the district emergency contacts that owns the EmergencyContact.
     *
     * @return HasMany
     */
    public function districtEmergencyContacts(): HasMany
    {
        return $this->hasMany(DistrictEmergencyContact::class);
    }

    /**
     * Interact with the emergency contact department type.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function departmentType(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst($value),
            set: fn ($value) => ucfirst($value),
        );
    }

    /**
     * Interact with the emergency contact name.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value,
        );
    }

    /**
     * Interact with the emergency contact number.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function contactNo(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value,
        );
    }

    /**
     * Interact with the emergency contact coverage mode.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function coverageMode(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value == CoverageMode::NATIONWIDE->value ? ucfirst(strtolower(coverageMode::NATIONWIDE->name)) : ucwords(strtolower(CoverageMode::PROVINCE->name)),
            set: fn ($value) => $value,
        );
    }

    /**
     * Interact with the emergency contact status.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function isActive(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value,
        );
    }

    /**
     * Get the EmergencyContact coverage.
     *
     * @return Attribute
     */
    protected function getCoverageAttribute(): string
    {
        $province = '';

        if ($this->coverage_mode == ucfirst(strtolower(CoverageMode::NATIONWIDE->name))) {
            $coverage_area = Str::title(CoverageMode::NATIONWIDE->name);
        } elseif ($this->coverage_mode == ucfirst(strtolower(CoverageMode::PROVINCE->name))) {
            $districts = $this->districtEmergencyContacts->where('emergency_contact_id', $this->id);

            foreach ($districts as $district) {
                $province .= $district->thailandDistrict->name_in_english.', ';
            }

            $coverage_area = '<b>'.__('By Province').'</b><br/>'.$province;
        } else {
            $coverage_area = '-';
        }

        return $coverage_area;
    }
}
