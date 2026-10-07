<?php

namespace App\Models\Sgoc;

use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\SgocUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Company extends Model
{
    protected $connection = 'sgoc';

    protected $table = 'companies';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    /**
     * Get the Security Guard Operation Center user's of the Company
     *
     * @return HasMany
     */
    public function sgoc()
    {
        return $this->hasMany(SgocUser::class);
    }
}
