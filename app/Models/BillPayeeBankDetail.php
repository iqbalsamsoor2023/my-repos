<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillPayeeBankDetail extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bill_payee_setting_id',
        'bank_id',
        'payee_account_name',
        'payee_account_name_th',
        'payee_account_number',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the bill payee setting that owns the BillPayeeBankDetail.
     *
     * @return BelongsTo
     */
    public function billPayeeSetting(): BelongsTo
    {
        return $this->belongsTo(BillPayeeSetting::class);
    }

    /**
     * Get the bank that owns the BillPayeeBankDetail.
     *
     * @return BelongsTo
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Get the transactions that owns the BillPayeeBankDetail.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
