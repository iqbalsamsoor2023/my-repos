<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected function paymentDetails(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['payment_mode'].' ('.$this->attributes['payment_prefix'].')',
        );
    }
}
