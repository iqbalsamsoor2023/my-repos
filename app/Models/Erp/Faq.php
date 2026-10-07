<?php

namespace App\Models\Erp;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faq extends BaseModel
{
    use SoftDeletes;

    protected $connection = 'mmbcnerp';

    protected $table = 'faqs';

    protected $fillable = [
        'faq_type_id',
        'question',
        'answer',
    ];

    protected $casts = [
        'question' => 'json',
        'answer' => 'json',
    ];

    /**
     * Get the faqType that owns the Faq
     */
    public function faqType(): BelongsTo
    {
        return $this->belongsTo(FaqType::class);
    }
}
