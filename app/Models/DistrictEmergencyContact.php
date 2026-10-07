<?php

namespace App\Models;

use App\Models\Erp\ThailandDistrict;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistrictEmergencyContact extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'emergency_contact_id',
        'thailand_district_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the emergency contact that owns by the DistrictEmergencyContact.
     *
     * @return BelongsTo
     */
    public function emergencyContact(): BelongsTo
    {
        return $this->belongsTo(EmergencyContact::class);
    }

    /**
     * Get the thailand district that owns the DistrictEmergencyContact.
     *
     * @return BelongsTo
     */
    public function thailandDistrict(): BelongsTo
    {
        return $this->belongsTo(ThailandDistrict::class);
    }
}
