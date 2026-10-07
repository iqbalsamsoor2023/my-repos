<?php

namespace App\Models;

use App\Services\ModelHistory\RecordModelHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Item extends Model
{
    use RecordModelHistory;

    protected $fillable = [
        'invoice_id',
        'name',
        'quantity',
        'price',
        'vat',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Get the invoice that owns the Item.
     *
     * @return BelongsTo
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the model histories that owns by Item.
     *
     * @return MorphMany
     */
    public function histories(): MorphMany
    {
        return $this->morphMany(ModelHistory::class, 'modelable');
    }
}
