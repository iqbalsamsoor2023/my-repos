<?php

namespace App\Models;

class PmCreditNoteDetail extends BaseModel
{
    protected $fillable = [
        'pm_credit_note_id',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function pmCreditNote()
    {
        return $this->belongsTo(PmCreditNote::class);
    }
}
