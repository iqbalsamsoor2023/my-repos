<?php

namespace App\Models;

use App\Enums\PmAccounting\BillingStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmBilling extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_type_id',
        'billing_number',
        'billing_date',
        'due_date',
        'issued_by',
        'residence_id',
        'unit_id',
        'issue_to_id',
        'issue_to_name',
        'service_duration_from',
        'service_duration_until',
        'unit',
        'rate_per_unit',
        'amount',
        'penalty_amount',
        'vat_amount',
        'discount_amount',
        'total',
        'status',
        'notes',
        'is_issued',
        'issued_at',
        'terms_and_conditions',
        'calculation',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'due_date' => 'date',
        'is_issued' => 'boolean',
        'amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'status' => BillingStatus::class,
        'calculation' => 'array',
    ];

    public function invoiceType()
    {
        return $this->belongsTo(InvoiceType::class);
    }

    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    public function residenceUnit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function unitUser()
    {
        return $this->belongsTo(User::class, 'issue_to_id');
    }

    public function issuedUser()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get invoices associated with this billing through the pivot table
     */
    public function invoices()
    {
        return $this->belongsToMany(
            PmInvoice::class,
            'pm_group_invoice',
            'pm_billing_id',
            'pm_invoice_id'
        )->withTimestamps()
            ->using(PmGroupInvoice::class);
    }
}
