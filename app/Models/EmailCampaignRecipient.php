<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailCampaignRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_campaign_id',
        'email',
        'status',
    ];

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class);
    }
}
