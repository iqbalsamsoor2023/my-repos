<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SupportTicketCategory extends BaseModel
{
    protected $connection = 'mmbcnerp';

    protected $table = 'support_ticket_categories';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected $fillable = [
        'name',
        'source_id',
        'platform_identifier',
    ];

    /**
     * Get the user that assigned to the SupportTicket.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->setConnection('mysql')->belongsTo(Residence::class, 'source_id', 'id');
    }
}
