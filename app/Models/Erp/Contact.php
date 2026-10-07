<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

class Contact extends Model
{
    use HasFactory;

    protected $connection = 'mmbcnerp';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected $fillable = [
        'contactable_type',
        'contactable_id',
        'company_category_id',
        'contact_position_id',
        'contact_details',
    ];

    protected $casts = [
        'contact_details' => AsCollection::class,
    ];

    /**
     * Get the parent of contactable model.
     *
     * @return MorphTo
     */
    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }
}
