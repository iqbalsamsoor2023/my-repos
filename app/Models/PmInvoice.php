<?php

namespace App\Models;

use App\Enums\PmAccounting\InvoiceStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmInvoice extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'subject',
        'invoice_number',
        'invoice_date',
        'due_date',
        'issued_by',
        'residence_id',
        'unit_id',
        'issue_to_id',
        'issue_to_name',
        'total_amount',
        'total_penalty_amount',
        'total_vat_amount',
        'total_discount_amount',
        'grand_total',
        'paid_amount',
        'credit_amount',
        'outstanding_amount',
        'status',
        'remarks',
        'internal_note',
        'terms_and_conditions',
        'parent_invoice_id',
        'has_credit_note',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'total_penalty_amount' => 'decimal:2',
        'total_vat_amount' => 'decimal:2',
        'total_discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'status' => InvoiceStatus::class,
        'has_credit_note' => 'boolean',
    ];

    /**
     * Get all billings associated with this invoice
     */
    public function billings()
    {
        return $this->belongsToMany(
            PmBilling::class,
            'pm_group_invoice',
            'pm_invoice_id',
            'pm_billing_id'
        )
            ->withTimestamps()
            ->using(PmGroupInvoice::class);
    }

    /**
     * Get the residence associated with this invoice
     */
    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the unit associated with this invoice
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the user who issued this invoice
     */
    public function issuedUser()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the user this invoice is issued to
     */
    public function issueToUser()
    {
        return $this->belongsTo(User::class, 'issue_to_id');
    }

    /**
     * Get the parent invoice
     */
    public function parentInvoice()
    {
        return $this->belongsTo(PmInvoice::class, 'parent_invoice_id');
    }

    /**
     * Get the child invoices
     */
    public function outstandingInvoices()
    {
        return $this->hasMany(PmInvoice::class, 'parent_invoice_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(PmCreditNote::class, 'pm_invoice_id');
    }
}
