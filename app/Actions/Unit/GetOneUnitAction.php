<?php

namespace App\Actions\Unit;

use App\Models\Unit;

class GetOneUnitAction
{
    public function execute(int $id)
    {
        return Unit::with(
            'residence',
            'residence.subdistrict',
            'residence.subdistrict.district',
            'residence.subdistrict.district.province')
            ->findOrFail($id);
    }
}
