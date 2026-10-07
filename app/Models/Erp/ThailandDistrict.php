<?php

namespace App\Models\Erp;

use App\Models\DistrictEmergencyContact;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ThailandDistrict extends Model
{
    use HasFactory;

    protected $connection = 'mmbcnerp';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'thailand_districts';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected $fillable = [
        'id',
        'code',
        'name_in_english',
        'name_in_thai',
        'province_id',
    ];

    /**
     * Get the district emergency contacts that owns the ThailandDistrict.
     *
     * @return HasMany
     */
    public function districtEmergencyContacts(): HasMany
    {
        return $this->hasMany(DistrictEmergencyContact::class);
    }

    /**
     * Get the ThailandSubDistrict for the ThailandDistrict.
     *
     *
     *
     * @return HasMany
     */
    public function subDistricts(): HasMany
    {
        return $this->hasMany(ThailandSubDistrict::class, 'district_id', 'id');
    }

    /**
     * Get the ThailandProvince that owns the ThailandDistrict.
     *
     * @return BelongsTo
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(ThailandProvince::class);
    }

    protected function nameInEnglish(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'th' ? ($this->attributes['name_in_thai'] ?? '') : ($this->attributes['name_in_english'] ?? ''),
        );
    }
}
