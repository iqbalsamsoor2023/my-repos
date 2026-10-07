<?php

namespace App\Models;

use App\Enums\Bill\BillStatus;
use App\Services\ModelHistory\RecordModelHistory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Invoice extends BaseModel implements HasMedia
{
    use InteractsWithMedia, RecordModelHistory;

    protected $fillable = [
        'invoice_no',
        'bill_payee_setting_id',
        'payer_unit_id',
        'bill_no',
        'bill_date',
        'due_date',
        'status',
        'total_amount',
        'amount_due',
        'remark',
        'created_at',
        'updated_at',
    ];

    protected $ignoreEvents = ['Created'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'status_name',
    ];

    /**
     * Get the bill payee setting that owns the Invoice.
     *
     * @return BelongsTo
     */
    public function billPayeeSetting(): BelongsTo
    {
        return $this->belongsTo(BillPayeeSetting::class);
    }

    /**
     * Get the unit that owns the Invoice.
     *
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'payer_unit_id', 'id');
    }

    /**
     * Get the payers that owns the Invoice.
     *
     * @return HasMany
     */
    public function payers(): HasMany
    {
        return $this->hasMany(Payer::class);
    }

    /**
     * Get the items that owns the Invoice.
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Get the transactions that owns the Invoice.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Interact with the activation status name.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function statusName(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                switch ($this->status) {
                    case BillStatus::UNPAID->value:
                        $status = ucfirst(strtolower(BillStatus::UNPAID->name));
                        break;
                    case BillStatus::PAID->value:
                        $status = ucfirst(strtolower(BillStatus::PAID->name));
                        break;
                    case BillStatus::PARTIALLY_PAID->value:
                        $status = Str::camel(strtolower(BillStatus::PARTIALLY_PAID->name));
                        break;
                    case BillStatus::PENDING->value:
                        $status = ucfirst(strtolower(BillStatus::PENDING->name));
                        break;
                    case BillStatus::FAILED->value:
                        $status = ucfirst(strtolower(BillStatus::FAILED->name));
                        break;
                    case BillStatus::CANCEL->value:
                        $status = ucfirst(strtolower(BillStatus::CANCEL->name));
                        break;
                    default:
                        $status = null;
                        break;
                }

                return $status;
            }
        );
    }
}
