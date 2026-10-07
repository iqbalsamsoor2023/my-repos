<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatCategoryItem extends Model
{
    protected $connection = 'mmbcnerp';

    protected $table = 'chat_category_items';

    protected $fillable = [
        'chat_category_id',
        'platform_identifier',
        'title',
        'title_in_thai',
        'is_active',
    ];

    /**
     * Get the user that belongs to the ChatSetting.
     *
     * @return BelongsTo
     */
    public function chatCategory(): BelongsTo
    {
        return $this->belongsTo(ChatCategory::class);
    }
}
