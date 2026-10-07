<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Sgoc\ModelHasRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SgocUser extends Model
{
    // DEPRECATED. Replace by Sgoc/User
    use HasFactory, SoftDeletes;

    protected $connection = 'sgoc';

    protected $table = 'users';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected $fillable = [
        'company_id',
    ];

    /**
     * Get details of the user roles.
     *
     * @return HasMany
     */
    public function userRoles()
    {
        return $this->hasMany(ModelHasRole::class, 'model_id', 'id');
    }

    public function residenceGuard(): HasOne
    {
        return $this->setConnection('mysql')->hasOne(Residence::class, 'sgoc_residence_guard_user_id', 'id');
    }
}
