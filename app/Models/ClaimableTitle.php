<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClaimableTitle extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'name_in_thai',
        'category',
        'type',
    ];
}
