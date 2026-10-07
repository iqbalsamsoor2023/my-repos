<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $connection = 'mmbcnerp';

    protected $table = 'invoices';
}
