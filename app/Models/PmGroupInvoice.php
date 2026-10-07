<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PmGroupInvoice extends Pivot
{
    public $incrementing = true;

    protected $table = 'pm_group_invoice';

    protected $fillable = [
        'pm_invoice_id',
        'pm_billing_id',
    ];

    public function billing()
    {
        return $this->belongsTo(PmBilling::class, 'pm_billing_id');
    }

    public function invoice()
    {
        return $this->belongsTo(PmInvoice::class, 'pm_invoice_id');
    }
}
