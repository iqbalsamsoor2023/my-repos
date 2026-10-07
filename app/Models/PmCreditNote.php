<?php

namespace App\Models;

use App\Enums\PmAccounting\CreditNoteStatus;
use App\Enums\PmAccounting\CreditNoteType;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmCreditNote extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'credit_note_number',
        'pm_invoice_id',
        'reason',
        'invoice_total',
        'credit_note_total',
        'type',
        'status',
        'issued_by',
        'issued_at',
    ];

    protected $casts = [
        'invoice_total' => 'decimal:2',
        'credit_note_total' => 'decimal:2',
        'type' => CreditNoteType::class,
        'status' => CreditNoteStatus::class,
        'issued_at' => 'datetime',
    ];

    public function pmInvoice()
    {
        return $this->belongsTo(PmInvoice::class);
    }

    public function issuedUser()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function creditNoteDetails()
    {
        return $this->hasMany(PmCreditNoteDetail::class, 'pm_credit_note_id');
    }

    public static function creditNoteNumber()
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $prefix = "CN$year$month";

        // Fetch all credit note numbers for the given prefix (including soft-deleted ones)
        $existingCreditNotes = PmCreditNote::withTrashed()
            ->where('credit_note_number', 'like', "$prefix%")
            ->pluck('credit_note_number')
            ->map(function ($creditNoteNumber) use ($prefix) {
                return (int) substr($creditNoteNumber, strlen($prefix));
            })
            ->sort()
            ->values()
            ->toArray();

        // Find the first missing number in the sequence
        $newNumber = 1;
        foreach ($existingCreditNotes as $index => $number) {
            if ($number != $index + 1) {
                $newNumber = $index + 1; // Use the first missing number
                break;
            }
            $newNumber = $number + 1; // If no gap, use the next number after the last
        }

        // Format the new credit note number
        $running = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        $creditNoteNumber = "$prefix$running";

        return $creditNoteNumber;
    }
}
