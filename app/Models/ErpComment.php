<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ErpComment extends Model
{
    use SoftDeletes;

    protected $connection = 'mmbcnerp';

    protected $table = 'comments';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parent_id',
        'user_id',
        'commentable_type',
        'commentable_id',
        'content',
        'read_at',
    ];

    /**
     * Get the user that writes the Comment.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->setConnection('mysql')->belongsTo(User::class);
    }

    /**
     * Get the parent commentable model (maintenance).
     *
     * @return MorphTo
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
