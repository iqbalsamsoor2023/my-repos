<?php

namespace App\Models;

use App\Enums\ResalesManagement\ResalesManagementStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResaleAdvertisement extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['created_at', 'updated_at'];

    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function scopeAvailableForResale(Builder $query): void
    {
        $query->where($this->getTable().'.status', ResalesManagementStatus::FOR_RESALE->value)->where('is_active', 1);
    }
}
