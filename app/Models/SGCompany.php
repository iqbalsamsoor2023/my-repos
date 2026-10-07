<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;

class SGCompany extends Model
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $connection = 'sgoc';

    protected $table = 'companies';

    protected $fillable = [
        'id',
        'province_id',
        'user_id',
        'type',
        'name',
        'name_th',
        'contact_email',
        'contact_number',
        'address',
        'person_in_charges',
        'website_url',
    ];

    protected $casts = [
        'person_in_charges' => 'array',
    ];
}
