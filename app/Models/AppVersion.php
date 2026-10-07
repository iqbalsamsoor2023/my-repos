<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class AppVersion extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'application_id',
        'platform',
        'application_name',
        'package_identifier',
        'build_version',
        'application_version',
        'force_update',
        'release_notes',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
