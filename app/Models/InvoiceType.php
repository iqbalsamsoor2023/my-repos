<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceType extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
    ];

    public function pmBillings()
    {
        return $this->hasMany(PmBilling::class);
    }
}
