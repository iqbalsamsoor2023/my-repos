<?php

namespace App\Models\Sgoc;

use App\Models\Residence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Round extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = "sgoc";
    protected $fillable = [
        'mmb_residence_id',
        'round_number',
        'start_time',
        'end_time',
        'is_active',
        'user_id',
    ];

    /**
     * Get the residence that owns the Round.
     */
    public function residence()
    {
        return $this->belongsTo(Residence::class, 'mmb_residence_id');
    }

    /**
     * Get the guard responsible for the round.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all checkpoint rounds for this round ordered by sequence.
     */
    public function checkpointRounds()
    {
        return $this->hasMany(CheckpointRound::class);
    }

    /**
     * Access the checkpoints assigned to this round.
     */
    public function checkpoints()
    {
        return $this->belongsToMany(Checkpoint::class, 'checkpoint_round');
    }

    /**
     * Scope a query to only include active rounds.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
