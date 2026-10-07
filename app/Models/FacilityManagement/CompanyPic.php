<?php

namespace App\Models\FacilityManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPic extends Model
{
    use HasFactory;

    protected $connection = 'fm';

    protected $table = 'company_pics';

    /**
     * Get the company that owns by Company Pic.
     *
     * @return BelongsTo
     */
    public function residenceCompany(): BelongsTo
    {
        return $this->belongsTo(ResidenceCompany::class, 'residence_company_id ', 'id');
    }
}
