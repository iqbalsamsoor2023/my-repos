<?php

namespace App\Models\Sgoc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ModelHasRole extends Model
{
    protected $connection = 'sgoc';

    protected $table = 'model_has_roles';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    /**
     * Get the roles that belongs to ModelHasRole
     *
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the user that belongs to ModelHasRole
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'model_id', 'id');
    }
}
