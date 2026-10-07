<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateClaimSupplier extends Model
{
    protected $fillable = [
        'private_claim_item_title_id',
        'residence_id',
        'company_name',
        'company_name_th',
        'brand',
        'contact_person_name',
        'contact_person_mobile_no',
        'contact_person_email'
    ];

    public function privateClaimItemTitle(): BelongsTo
    {
        return $this->belongsTo(PrivateClaimItemTitle::class);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }
}
