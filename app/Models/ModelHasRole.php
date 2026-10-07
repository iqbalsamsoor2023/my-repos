<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ModelHasRole extends Model
{
    /**
     * Get the user record for the model role.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'model_id');
    }

    /**
     * Get the role record for the model role.
     *
     * @return BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
