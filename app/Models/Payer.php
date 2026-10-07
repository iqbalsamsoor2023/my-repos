<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payer extends Model
{
    protected $fillable = [
        'invoice_id',
        'payer_id',
    ];

    /**
     * Get the invoice that owns the Payer.
     *
     * @return BelongsTo
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the user that pays the invoice.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id', 'id');
    }
}
