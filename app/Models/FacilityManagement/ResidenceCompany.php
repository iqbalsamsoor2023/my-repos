<?php

namespace App\Models\FacilityManagement;

use App\Models\Residence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidenceCompany extends Model
{
    use HasFactory;

    protected $connection = 'fm';

    protected $table = 'residence_companies';

    /**
     * Get the residence that owns by Company.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class, 'mmb_residence_id ', 'id');
    }

    /**
     * Get the company that owns by Residence.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id ', 'id');
    }
}
