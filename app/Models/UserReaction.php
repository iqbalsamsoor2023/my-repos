<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserReaction extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    public function announcement()
    {
        return $this->morphTo();
    }
}
