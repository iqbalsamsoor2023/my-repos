<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SgocCompany extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'sgoc';

    protected $table = 'companies';

    /**
     * Get the user of the SGOC Company
     *
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(SgocUser::class);
    }
}
