<?php

namespace App\Actions\Residence;

use App\Models\Residence;

class GetOneResidenceAction
{
    public function execute(int $id)
    {
        return Residence::with([
            'propertyManagementUser:id',
            'developer:id',
            'subdistrict:id,district_id',
            'subdistrict.district:id,province_id',
            'subdistrict.district.province:id',
        ])->find($id);
    }
}