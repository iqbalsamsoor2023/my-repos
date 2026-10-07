<?php

namespace App\Models\Erp;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaqType extends BaseModel
{
    use SoftDeletes;

    protected $connection = 'mmbcnerp';

    protected $table = 'faq_types';

    protected $fillable = [
        'platform_id',
        'name',
        'name_in_thai',
        'slug',
        'is_active',
    ];

    /**
     * Get the faqType that owns the Faq
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    /**
     * Get all of the faqs for the FaqType
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }
}
