<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivationStatus extends Model
{
    use SoftDeletes;

    protected $connection = 'mmbcnerp';

    protected $table = 'activation_statuses';

    protected $fillable = ['name'];
}
