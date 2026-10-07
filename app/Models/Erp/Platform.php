<?php

namespace App\Models\Erp;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Platform extends BaseModel
{
    use SoftDeletes;

    protected $connection = 'mmbcnerp';

    protected $table = 'platforms';

    protected $fillable = ['name'];
}
