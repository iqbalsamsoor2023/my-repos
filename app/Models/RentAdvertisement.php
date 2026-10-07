<?php

namespace App\Models;

use App\Enums\TenancyManagement\TenancyManagementStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentAdvertisement extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['deleted_at'];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    public function custom_rental_contracts()
    {
        return $this->hasMany(CustomRentalContract::class, 'unit_id', 'unit_id');
    }

    public function scopeAvailableForRent(Builder $query): void
    {
        $query->where('tenancy_status', TenancyManagementStatus::FOR_RENT->value);
    }
}
