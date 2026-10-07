<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateClaimItemSetting extends Model
{
    protected $fillable = [
        'residence_id',
        'private_claim_item_id',
        'private_claim_item_details',
        'warranty_period',
        'supplier',
        'is_out_warranty',
        'remark',
        'supplier_company_name',
        'supplier_company_name_th',
        'supplier_item_brand',
        'pic_name',
        'pic_mobile_no',
        'pic_email',
    ];

    protected $appends = ['warranty_period_formatted'];

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function privateClaimItem(): BelongsTo
    {
        return $this->belongsTo(PrivateClaimItem::class);
    }

    public function getWarrantyPeriodFormattedAttribute()
    {
        $months = $this->warranty_period ?? 0;

        $years = intdiv($months, 12);
        $remainingMonths = $months % 12;

        return ($years ? $years . ' year' . ($years > 1 ? 's ' : ' ') : '') .
            ($remainingMonths ? $remainingMonths . ' month' . ($remainingMonths > 1 ? 's' : '') : '');
    }
}
