<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    // protected $table = 'error_logs';

    protected $fillable = [
        'type',
        'file',
        'error_summary',
        'log_trace',
    ];
}
