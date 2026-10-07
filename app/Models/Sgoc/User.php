<?php

namespace App\Models\Sgoc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class User extends Model
{
    protected $connection = 'sgoc';

    protected $table = 'users';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    /**
     * Get all of the roles for the User
     *
     * @return HasMany
     */
    public function roles(): HasMany
    {
        return $this->hasMany(ModelHasRole::class, 'model_id');
    }
}
