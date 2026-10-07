<?php

namespace App\Models;

use App\Enums\SalesManagement\SellingStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleAdvertisement extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function available_payments(): BelongsToMany
    {
        return $this->belongsToMany(AvailablePayment::class, 'sale_advertisement_available_payments');
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function scopeAvailableForSale(Builder $query): Builder
    {
        return $query->where('selling_status', (string) SellingStatusEnum::NOT_YET_SOLD->value)
            ->where($this->getTable().'.is_active', 1);
    }

    public function scopeSoldOut(Builder $query): Builder
    {
        return $query->where('selling_status', '<>', (string) SellingStatusEnum::NOT_YET_SOLD->value)
            ->where($this->getTable().'.is_active', 1);
    }
}
