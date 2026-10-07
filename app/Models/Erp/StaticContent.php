<?php

namespace App\Models\Erp;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class StaticContent extends BaseModel
{
    use HasTranslations, SoftDeletes;

    protected $connection = 'mmbcnerp';

    protected $table = 'static_contents';

    protected $fillable = [
        'type',
        'content',
    ];

    public array $translatable = ['content'];
}
