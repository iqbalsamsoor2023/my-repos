<?php

namespace App\Models\Sgoc;

use App\Models\CheckpointLog;
use App\Models\Residence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checkpoint extends Model
{
    use HasFactory;

    protected $connection = 'sgoc';

    /**
     * Get the checkpoint logs that owns the Checkpoint.
     *
     * @return HasMany
     */
    public function checkpointLogs(): HasMany
    {
        return $this->hasMany(CheckpointLog::class);
    }

    /**
     * Get the checkpoint questionnaires that owns the Checkpoint.
     *
     * @return HasMany
     */
    public function checkpointQuestionnaires(): HasMany
    {
        return $this->hasMany(CheckpointQuestionnaire::class);
    }

    /**
     * Get the residence that owns the Checkpoint.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->setConnection('mysql')->belongsTo(Residence::class, 'mmb_residence_id');
    }

    /**
     * Get the zone that owns the Checkpoint.
     *
     * @return BelongsTo
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id', 'id');
    }
}
