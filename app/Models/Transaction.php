<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Transaction extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'ref_no',
        'invoice_id',
        'bill_payee_bank_detail_id',
        'payment_id',
        'paid_amount',
        'transaction_datetime',
        'status',
        'payer_name',
        'reviewed_by',
        'remark',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the invoice that owns the Transaction.
     *
     * @return BelongsTo
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the bank account detail that owns the Transaction.
     *
     * @return BelongsTo
     */
    public function bankAccountDetail(): BelongsTo
    {
        return $this->belongsTo(BillPayeeBankDetail::class, 'bill_payee_bank_detail_id', 'id');
    }

    /**
     * Get the payment method that owns the Transaction.
     *
     * @return BelongsTo
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'id');
    }

    /**
     * Get the user that reviewed the Transaction.
     *
     * @return BelongsTo
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }
}
