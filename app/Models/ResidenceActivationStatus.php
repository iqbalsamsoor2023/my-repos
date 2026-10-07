<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResidenceActivationStatus extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'residence_activation_statuses';

    protected $fillable = [
        'status',
    ];

    /**
     * Get all of the residences for the ResidenceActivationStatus
     *
     * @return HasMany
     */
    public function residences()
    {
        return $this->hasMany(Residence::class, 'residence_activation_status_id');
    }
}
