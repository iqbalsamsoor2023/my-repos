<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HuaweiAccessToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_type',
        'access_token',
        'expires_in',
    ];

    /**
     * Get the difference between the current datetime and created_at value.
     *
     * @return int
     */
    public function getExpiresInAttribute()
    {
        $expires_in = $this->created_at->addMinutes(50);

        return $expires_in;
    }
}
