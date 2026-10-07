<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutoSendReport extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'email',
        'residence_id',
        'time',
        'hour',
        'module_type',
        'created_at',
        'updated_at',
    ];

    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }
}
