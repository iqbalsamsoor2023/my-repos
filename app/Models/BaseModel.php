<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class BaseModel extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
