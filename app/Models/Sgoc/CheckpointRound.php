<?php

namespace App\Models\Sgoc;

use App\Models\CheckpointLog;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckpointRound extends Pivot
{
    use SoftDeletes;

    protected $connection = 'sgoc';

    protected $table = 'checkpoint_round';

    protected $fillable = [
        'checkpoint_id',
        'round_id',
        'sequence',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'checked_at' => 'datetime',
    ];
    
    /**
     * Get the checkpoint of the CheckpointRound
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function checkpoint()
    {
        return $this->belongsTo(Checkpoint::class);
    }

    /**
     * Get the round of the CheckpointRound
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    /**
     * Get all checkpoint logs for this checkpoint round
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function checkpointLogs()
    {
        return $this->hasMany(CheckpointLog::class, 'checkpoint_round_id');
    }
}
