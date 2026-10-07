<?php

namespace App\Models\FacilityManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $connection = 'fm';

    protected $table = 'companies';
}
