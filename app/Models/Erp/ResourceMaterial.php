<?php

namespace App\Models\Erp;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class ResourceMaterial extends BaseModel
{
    use HasTranslations, SoftDeletes;

    protected $connection = 'mmbcnerp';

    protected $table = 'resource_materials';

    protected $fillable = [
        'platform_id',
        'type',
        'title',
        'source_link',
    ];

    public array $translatable = ['title'];

    /**
     * Get the faqType that owns the Faq
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }
}
