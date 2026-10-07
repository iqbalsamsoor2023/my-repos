<?php

namespace App\Models\Sgoc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckpointQuestionnaire extends Model
{
    use HasFactory;

    protected $connection = 'sgoc';

    public function checkpoint()
    {
        return $this->belongsTo(Checkpoint::class);
    }
}
